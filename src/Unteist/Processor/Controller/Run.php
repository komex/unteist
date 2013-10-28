<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Processor\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Unteist\Assert\Assert;
use Unteist\Event\EventStorage;
use Unteist\Event\TestCaseEvent;
use Unteist\Exception\IncompleteTestException;
use Unteist\Exception\TestErrorException;
use Unteist\Exception\TestFailException;
use Unteist\Meta\TestMeta;
use Unteist\Event\MethodEvent;
use Unteist\Processor\Runner;
use Unteist\Strategy\Context;

/**
 * Class Run
 *
 * @package Unteist\Processor\Controller
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class Run implements ControllerChildConfigurableInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;
    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;
    /**
     * @var EventDispatcherInterface
     */
    protected $precondition;
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
     * @var ControllerParentInterface
     */
    protected $parent;
    /**
     * @var Runner
     */
    protected $runner;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->dispatcher = $container->get('dispatcher');
        $this->context = $container->get('context');
    }

    /**
     * @param Runner $runner
     */
    public function setRunner(Runner $runner)
    {
        $this->runner = $runner;
    }

    /**
     * @param EventDispatcherInterface $precondition
     */
    public function setPrecondition(EventDispatcherInterface $precondition)
    {
        $this->precondition = $precondition;
    }

    /**
     * Set parent controller for behavior controller.
     *
     * @param ControllerParentInterface $parent
     */
    public function setParent(ControllerParentInterface $parent)
    {
        $this->parent = $parent;
    }

    /**
     * Actions before each test.
     *
     * @param TestCaseEvent $event
     */
    public function beforeCase(TestCaseEvent $event)
    {
        try {
            $this->dispatcher->dispatch(EventStorage::EV_BEFORE_CASE, $event);
            $this->precondition->dispatch(EventStorage::EV_BEFORE_CASE);
        } catch (\Exception $exception) {
            $this->context->onBeforeCase($exception);
            $event = $this->preconditionFailed($exception);
            /** @var Skip $controller */
            $controller = $this->parent->switchTo(ControllerParentInterface::CONTROLLER_SKIP);
            $controller->setDepends($event->getMethod());
        }
    }

    /**
     * Resolve test dependencies.
     *
     * @param TestMeta $test
     */
    public function resolveDependencies(TestMeta $test)
    {
        $this->runner->resolveDependencies($test);
    }

    /**
     * Get test data set.
     *
     * @param TestMeta $test
     *
     * @return array[]
     */
    public function getDataSet(TestMeta $test)
    {
        return $this->runner->getDataSet($test->getDataProvider());
    }

    /**
     * Actions before each test.
     *
     * @param MethodEvent $event
     */
    public function beforeTest(MethodEvent $event)
    {
        try {
            $this->dispatcher->dispatch(EventStorage::EV_BEFORE_TEST, $event);
            $this->precondition->dispatch(EventStorage::EV_BEFORE_TEST, $event);
        } catch (\Exception $exception) {
            $this->context->onBeforeTest($exception);
            $event = $this->preconditionFailed($exception);
            /** @var Skip $controller */
            $controller = $this->parent->switchTo(ControllerParentInterface::CONTROLLER_SKIP_ONCE);
            $controller->setDepends($event->getMethod());
        }
    }

    /**
     * Run test method.
     *
     * @param TestMeta $test Meta information about test method
     * @param MethodEvent $event Configured method event
     * @param array $dataSet Arguments for test
     *
     * @return int Status code
     */
    public function test(TestMeta $test, MethodEvent $event, array $dataSet)
    {
        try {
            $this->started = microtime(true);
            $this->asserts = Assert::getAssertsCount();
            $statusCode = $this->behavior($test, $event, $dataSet);
        } catch (TestFailException $exception) {
            $this->finish($test, $event, MethodEvent::METHOD_FAILED, $exception);
            $statusCode = $this->context->onFailure($exception);
        }

        return $statusCode;
    }

    /**
     * Actions after each test.
     *
     * @param MethodEvent $event
     */
    public function afterTest(MethodEvent $event)
    {
        try {
            $this->dispatcher->dispatch(EventStorage::EV_AFTER_TEST, $event);
            $this->precondition->dispatch(EventStorage::EV_AFTER_TEST, $event);
        } catch (\Exception $exception) {
            $this->context->onAfterTest($exception);
            $event = $this->preconditionFailed($exception);
            /** @var Skip $controller */
            $controller = $this->parent->switchTo(ControllerParentInterface::CONTROLLER_SKIP);
            $controller->setDepends($event->getMethod());
        }
    }

    /**
     * Actions after each case.
     *
     * @param TestCaseEvent $event
     */
    public function afterCase(TestCaseEvent $event)
    {
        try {
            $this->dispatcher->dispatch(EventStorage::EV_AFTER_CASE, $event);
            $this->precondition->dispatch(EventStorage::EV_AFTER_CASE);
        } catch (\Exception $exception) {
            $this->context->onAfterCase($exception);
            $this->preconditionFailed($exception);
        }
    }

    /**
     * Generate an event with information about failed precondition method.
     *
     * @param \Exception $exception
     *
     * @return MethodEvent
     */
    protected function preconditionFailed(\Exception $exception)
    {
        /** @var MethodEvent $event */
        $event = $this->container->get('event.method');
        $event->setStatus(MethodEvent::METHOD_FAILED);
        $event->parseException($exception);
        $this->dispatcher->dispatch(EventStorage::EV_METHOD_FAILED, $event);

        return $event;
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
    protected function behavior(TestMeta $test, MethodEvent $event, array $dataSet)
    {
        try {
            $statusCode = 0;
            call_user_func_array([$this->runner->getTestCase(), $test->getMethod()], $dataSet);
            if ($test->getExpectedException()) {
                throw new TestFailException('Expected exception ' . $test->getExpectedException());
            }
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
     * Method was finished.
     *
     * @param TestMeta $test
     * @param MethodEvent $event
     * @param int $status
     * @param \Exception $exception
     */
    private function finish(TestMeta $test, MethodEvent $event, $status, \Exception $exception = null)
    {
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
