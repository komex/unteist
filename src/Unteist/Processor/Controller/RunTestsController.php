<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Processor\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Unteist\Assert\Assert;
use Unteist\Event\EventStorage;
use Unteist\Event\MethodEvent;
use Unteist\Event\TestCaseEvent;
use Unteist\Exception\IncompleteTestException;
use Unteist\Exception\SkipTestException;
use Unteist\Exception\TestErrorException;
use Unteist\Exception\TestFailException;
use Unteist\Meta\TestMeta;
use Unteist\Processor\Runner;
use Unteist\Strategy\Context;

/**
 * Class RunTestsController
 *
 * @package Unteist\Processor\Controller
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class RunTestsController extends AbstractController
{
    /**
     * @var Context
     */
    protected $context;
    /**
     * @var float
     */
    protected $started;
    /**
     * @var int
     */
    protected $asserts;
    /**
     * @var Runner
     */
    protected $runner;
    /**
     * @var EventDispatcherInterface
     */
    protected $precondition;

    /**
     * @param Context $context
     */
    public function setContext(Context $context)
    {
        $this->context = $context;
    }

    /**
     * @param EventDispatcherInterface $precondition
     */
    public function setPrecondition(EventDispatcherInterface $precondition)
    {
        $this->precondition = $precondition;
    }

    /**
     * Before all tests.
     */
    public function beforeCase(TestCaseEvent $testCaseEvent)
    {
        try {
            $this->dispatcher->dispatch(EventStorage::EV_BEFORE_CASE, $testCaseEvent);
            $this->precondition->dispatch(EventStorage::EV_BEFORE_CASE);
        } catch (\Exception $exception) {
            $this->context->onBeforeCase($exception);
            $this->switchController($exception);
        }
    }

    /**
     * @param Runner $runner
     */
    public function setRunner(Runner $runner)
    {
        $this->runner = $runner;
    }

    /**
     * Run test.
     *
     * @param TestMeta $test
     *
     * @return int
     */
    public function test(TestMeta $test)
    {
        if ($test->getStatus() !== TestMeta::TEST_NEW && $test->getStatus() !== TestMeta::TEST_MARKED) {
            return 0;
        }
        try {
            $this->runner->resolveDependencies($test);
        } catch (SkipTestException $e) {
            /** @var AbstractController $controller */
            $controller = $this->container->get('controller.skip');

            return $controller->test($test);
        }
        $dataProvider = $this->runner->getDataSet($test->getDataProvider());
        $statusCode = 0;
        foreach ($dataProvider as $index => $dataSet) {
            /** @var MethodEvent $event */
            $event = $this->container->get('event.method');
            $event->configByTestMeta($test);
            if (count($dataProvider) > 1) {
                $event->setDataSet($index + 1);
            }
            $code = $this->execute($test, $event, $dataSet);
            if ($code > 0) {
                $statusCode = $code;
            }
        }

        return $statusCode;
    }

    /**
     * All tests done. Generate EV_AFTER_CASE event.
     */
    public function afterCase(TestCaseEvent $testCaseEvent)
    {
        try {
            $this->precondition->dispatch(EventStorage::EV_AFTER_CASE);
            parent::afterCase($testCaseEvent);
        } catch (\Exception $exception) {
            $this->context->onAfterCase($exception);
            $this->preconditionFailed($exception);
        }
    }

    /**
     * Before running method.
     *
     * @param MethodEvent $event
     */
    protected function beforeTest(MethodEvent $event)
    {
        parent::beforeTest($event);
        $this->precondition->dispatch(EventStorage::EV_BEFORE_TEST, $event);
    }

    /**
     * Test done. Generate EV_AFTER_TEST event.
     *
     * @param MethodEvent $event
     */
    protected function afterTest(MethodEvent $event)
    {
        try {
            $this->precondition->dispatch(EventStorage::EV_AFTER_TEST, $event);
        } catch (\Exception $exception) {
            $this->context->onAfterTest($exception);
            $this->switchController($exception);
        }
    }

    /**
     * Generate an event with information about failed precondition method.
     *
     * @param \Exception $exception
     *
     * @return SkipTestsController
     */
    protected function preconditionFailed(\Exception $exception)
    {
        /** @var MethodEvent $event */
        $event = $this->container->get('event.method');
        $event->setStatus(MethodEvent::METHOD_FAILED);
        $event->parseException($exception);
        $this->dispatcher->dispatch(EventStorage::EV_METHOD_FAILED, $event);
        /** @var SkipTestsController $controller */
        $controller = $this->container->get('controller.skip');
        $controller->setDepends($event->getMethod());

        return $controller;
    }

    /**
     * Switch controller to SkipTestsController.
     *
     * @param \Exception $exception
     */
    protected function switchController(\Exception $exception)
    {
        $controller = $this->preconditionFailed($exception);
        $this->runner->setController($controller);
    }

    /**
     * @param TestMeta $test
     * @param MethodEvent $event
     * @param array $dataSet
     *
     * @return int
     */
    private function execute(TestMeta $test, MethodEvent $event, array $dataSet)
    {
        try {
            $this->beforeTest($event);
        } catch (\Exception $exception) {
            $this->context->onBeforeTest($exception);
            $controller = $this->preconditionFailed($exception);
            $controller->test($test);

            return 1;
        }
        try {
            $this->started = microtime(true);
            $this->asserts = Assert::getAssertsCount();
            $statusCode = $this->convert($test, $event, $dataSet);
        } catch (TestFailException $exception) {
            $this->finish($test, $event, MethodEvent::METHOD_FAILED, $exception);
            $statusCode = $this->context->onFailure($exception);
        }

        return $statusCode;
    }

    /**
     * Method was finished.
     *
     * @param TestMeta $test
     * @param MethodEvent $event
     * @param int $status
     * @param \Exception $exception
     * @param bool $sendEvent
     */
    private function finish(
        TestMeta $test,
        MethodEvent $event,
        $status,
        \Exception $exception = null,
        $sendEvent = true
    ) {
        $event->setStatus($status);
        $event->setTime(floatval(microtime(true) - $this->started));
        $event->setAsserts(Assert::getAssertsCount() - $this->asserts);
        $this->asserts = Assert::getAssertsCount();
        if ($status === MethodEvent::METHOD_OK) {
            $test->setStatus(TestMeta::TEST_DONE);
            $this->dispatcher->dispatch(EventStorage::EV_METHOD_DONE, $event);
            $this->precondition->dispatch(EventStorage::EV_METHOD_DONE, $event);
        } else {
            $event->parseException($exception);
            $context = [
                'pid' => getmypid(),
                'method' => $event->getMethod(),
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
            ];
            /** @var LoggerInterface $logger */
            $logger = $this->container->get('logger');
            switch ($status) {
                case MethodEvent::METHOD_SKIPPED:
                    $test->setStatus(TestMeta::TEST_SKIPPED);
                    $event->setTime(0);
                    $logger->debug('The test was skipped.', $context);
                    $this->dispatcher->dispatch(EventStorage::EV_METHOD_SKIPPED, $event);
                    $this->precondition->dispatch(EventStorage::EV_METHOD_SKIPPED, $event);
                    break;
                case MethodEvent::METHOD_FAILED:
                    $test->setStatus(TestMeta::TEST_FAILED);
                    $logger->debug('Assert fail.', $context);
                    $this->dispatcher->dispatch(EventStorage::EV_METHOD_FAILED, $event);
                    $this->precondition->dispatch(EventStorage::EV_METHOD_FAILED, $event);
                    break;
                case MethodEvent::METHOD_INCOMPLETE:
                    $test->setStatus(TestMeta::TEST_INCOMPLETE);
                    $logger->debug('Test incomplete.', $context);
                    $this->dispatcher->dispatch(EventStorage::EV_METHOD_INCOMPLETE, $event);
                    $this->precondition->dispatch(EventStorage::EV_METHOD_INCOMPLETE, $event);
                    break;
                default:
                    $test->setStatus(TestMeta::TEST_FAILED);
                    $logger->critical('Unexpected exception.', $context);
                    $this->dispatcher->dispatch(EventStorage::EV_METHOD_FAILED, $event);
                    $this->precondition->dispatch(EventStorage::EV_METHOD_FAILED, $event);
            }
        }
        if ($sendEvent) {
            $this->afterTest($event);
        }
        parent::afterTest($event);
    }

    /**
     * Controller behavior.
     *
     * @param TestMeta $test
     * @param array $dataSet
     *
     * @throws TestFailException
     * @return int Status code
     */
    private function behavior(TestMeta $test, array $dataSet)
    {
        call_user_func_array([$this->runner->getTestCase(), $test->getMethod()], $dataSet);
        if ($test->getExpectedException()) {
            throw new TestFailException('Expected exception ' . $test->getExpectedException());
        }

        return 0;
    }

    /**
     * Convert exceptions using context.
     *
     * @param TestMeta $test
     * @param MethodEvent $event
     * @param array $dataSet
     *
     * @return int Status code
     */
    private function convert(TestMeta $test, MethodEvent $event, array $dataSet)
    {
        try {
            $statusCode = $this->behavior($test, $dataSet);
            $this->finish($test, $event, MethodEvent::METHOD_OK);
        } catch (TestErrorException $exception) {
            $statusCode = $this->context->onError($exception);
            $this->finish($test, $event, MethodEvent::METHOD_FAILED, $exception);
        } catch (IncompleteTestException $exception) {
            $statusCode = $this->context->onIncomplete($exception);
            $this->finish($test, $event, MethodEvent::METHOD_INCOMPLETE, $exception);
        } catch (\Exception $exception) {
            $statusCode = $this->exceptionControl($test, $event, $exception);
        }

        return $statusCode;
    }

    /**
     * Try to resolve situation with exception.
     *
     * @param TestMeta $test
     * @param MethodEvent $event
     * @param \Exception $exception
     *
     * @return int Status code
     */
    private function exceptionControl(TestMeta $test, MethodEvent $event, \Exception $exception)
    {
        if (is_a($exception, $test->getExpectedException())) {
            $code = $test->getExpectedExceptionCode();
            if ($code !== null && $code !== $exception->getCode()) {
                $error = new TestFailException(
                    sprintf(
                        'Failed asserting that expected exception code %d is equal to %d',
                        $code,
                        $exception->getCode()
                    ),
                    0,
                    $exception
                );
                $status = $this->context->onFailure($error);
                $this->finish($test, $event, MethodEvent::METHOD_FAILED, $exception);

                return $status;
            }
            $message = $test->getExpectedExceptionMessage();
            if ($message !== null && strpos($exception->getMessage(), $message) === false) {
                $error = new TestFailException(
                    sprintf(
                        'Failed asserting that exception message "%s" contains "%s"',
                        $exception->getMessage(),
                        $message
                    ),
                    0,
                    $exception
                );
                $status = $this->context->onFailure($error);
                $this->finish($test, $event, MethodEvent::METHOD_FAILED, $exception);

                return $status;
            }

            return 0;
        } else {
            $this->context->onUnexpectedException($exception);

            return 1;
        }
    }
}
