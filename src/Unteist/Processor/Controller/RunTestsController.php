<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Processor\Controller;

use Psr\Log\LoggerInterface;
use Unteist\Assert\Assert;
use Unteist\Event\EventStorage;
use Unteist\Event\MethodEvent;
use Unteist\Exception\IncompleteTestException;
use Unteist\Exception\SkipTestException;
use Unteist\Exception\TestErrorException;
use Unteist\Exception\TestFailException;
use Unteist\Meta\TestMeta;
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
        $this->runner->resolveDependencies($test);
        $dataProvider = $this->runner->getDataSet($test->getDataProvider());
        $status_code = 0;
        $this->context = $this->container->get('context');
        foreach ($dataProvider as $dp_number => $data_set) {
            $event = new MethodEvent();
            $event->setClass($test->getClass());
            $event->setMethod($test->getMethod());
            $event->setDepends($test->getDependencies());
            if (count($dataProvider) > 1) {
                $event->setDataSet($dp_number + 1);
            }
            try {
                $this->beforeTest($event);
            } catch (\Exception $e) {
                $this->finish($test, $event, MethodEvent::METHOD_SKIPPED, $e, false);
                continue;
            }
            $code = $this->execute($test, $event, $data_set);
            if ($code > 0) {
                $status_code = $code;
            }
        }

        return $status_code;
    }

    /**
     * All tests done. Generate EV_AFTER_CASE event.
     */
    public function afterCase()
    {
        $this->precondition->dispatch(EventStorage::EV_AFTER_CASE);
        parent::afterCase();
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
        } catch (\Exception $e) {
            $this->switchController($e);
        }
    }

    /**
     * @param TestMeta $test
     * @param MethodEvent $event
     * @param array $data_set
     *
     * @return int
     */
    private function execute(TestMeta $test, MethodEvent $event, array $data_set)
    {
        try {
            $this->started = microtime(true);
            $this->asserts = Assert::getAssertsCount();
            $status_code = $this->convert($test, $event, $data_set);
        } catch (SkipTestException $e) {
            $this->finish($test, $event, MethodEvent::METHOD_SKIPPED, $e, false);
            $status_code = 1;
        } catch (TestFailException $e) {
            $this->finish($test, $event, MethodEvent::METHOD_FAILED, $e);
            $status_code = $this->context->onFailure($e);
        } catch (IncompleteTestException $e) {
            $this->finish($test, $event, MethodEvent::METHOD_INCOMPLETE, $e);
            $status_code = $this->context->onIncomplete($e);
        }

        return $status_code;
    }

    /**
     * Method was finished.
     *
     * @param TestMeta $test
     * @param MethodEvent $event
     * @param int $status
     * @param \Exception $e
     * @param bool $send_event
     */
    private function finish(TestMeta $test, MethodEvent $event, $status, \Exception $e = null, $send_event = true)
    {
        $event->setStatus($status);
        $event->setTime(floatval(microtime(true) - $this->started));
        $event->setAsserts(Assert::getAssertsCount() - $this->asserts);
        $this->asserts = Assert::getAssertsCount();
        if ($e === null) {
            $context = [];
        } else {
            $event->parseException($e);
            $context = [
                'pid' => getmypid(),
                'method' => $event->getMethod(),
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ];
        }
        /** @var LoggerInterface $logger */
        $logger = $this->container->get('logger');
        switch ($status) {
            case MethodEvent::METHOD_OK:
                $test->setStatus(TestMeta::TEST_DONE);
                $this->dispatcher->dispatch(EventStorage::EV_METHOD_DONE, $event);
                break;
            case MethodEvent::METHOD_SKIPPED:
                $test->setStatus(TestMeta::TEST_SKIPPED);
                $logger->debug('The test was skipped.', $context);
                $this->dispatcher->dispatch(EventStorage::EV_METHOD_SKIPPED, $event);
                break;
            case MethodEvent::METHOD_FAILED:
                $test->setStatus(TestMeta::TEST_FAILED);
                $logger->debug('Assert fail.', $context);
                $this->dispatcher->dispatch(EventStorage::EV_METHOD_FAILED, $event);
                break;
            case MethodEvent::METHOD_INCOMPLETE:
                $test->setStatus(TestMeta::TEST_INCOMPLETE);
                $logger->debug('Test incomplete.', $context);
                $this->dispatcher->dispatch(EventStorage::EV_METHOD_INCOMPLETE, $event);
                break;
            default:
                $test->setStatus(TestMeta::TEST_FAILED);
                $logger->critical('Unexpected exception.', $context);
                $this->dispatcher->dispatch(EventStorage::EV_METHOD_FAILED, $event);
        }
        if ($send_event) {
            $this->afterTest($event);
        }
        parent::afterTest($event);
    }

    /**
     * Controller behavior.
     *
     * @param TestMeta $test
     * @param array $data_set
     *
     * @throws TestFailException
     * @return int Status code
     */
    private function behavior(TestMeta $test, array $data_set)
    {
        call_user_func_array([$this->runner->getTestCase(), $test->getMethod()], $data_set);
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
     * @param array $data_set
     *
     * @return int Status code
     */
    private function convert(TestMeta $test, MethodEvent $event, array $data_set)
    {
        try {
            $status_code = $this->behavior($test, $data_set);
            $this->finish($test, $event, MethodEvent::METHOD_OK);
        } catch (TestFailException $e) {
            $status_code = $this->context->onFailure($e);
            $this->finish($test, $event, MethodEvent::METHOD_FAILED, $e);
        } catch (TestErrorException $e) {
            $status_code = $this->context->onError($e);
            $this->finish($test, $event, MethodEvent::METHOD_FAILED, $e);
        } catch (IncompleteTestException $e) {
            $status_code = $this->context->onIncomplete($e);
            $this->finish($test, $event, MethodEvent::METHOD_INCOMPLETE, $e);
        } catch (\Exception $e) {
            $status_code = $this->exceptionControl($test, $e);
        }

        return $status_code;
    }

    /**
     * Try to resolve situation with exception.
     *
     * @param TestMeta $test
     * @param \Exception $e
     *
     * @return int Status code
     */
    private function exceptionControl(TestMeta $test, \Exception $e)
    {
        if (is_a($e, $test->getExpectedException())) {
            $code = $test->getExpectedExceptionCode();
            if ($code !== null && $code !== $e->getCode()) {
                $error = new TestFailException(
                    sprintf(
                        'Failed asserting that expected exception code %d is equal to %d',
                        $code,
                        $e->getCode()
                    ),
                    0,
                    $e
                );

                return $this->context->onFailure($error);
            }
            $message = $test->getExpectedExceptionMessage();
            if ($message !== null && strpos($e->getMessage(), $message) === false) {
                $error = new TestFailException(
                    sprintf(
                        'Failed asserting that exception message "%s" contains "%s"',
                        $e->getMessage(),
                        $message
                    ),
                    0,
                    $e
                );

                return $this->context->onFailure($error);
            }

            return 0;
        } else {
            $this->context->onUnexpectedException($e);

            return 1;
        }
    }
}
