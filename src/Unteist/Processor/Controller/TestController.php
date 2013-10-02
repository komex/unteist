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
use Unteist\Event\TestEvent;
use Unteist\Exception\IncompleteTestException;
use Unteist\Exception\SkipTestException;
use Unteist\Exception\TestErrorException;
use Unteist\Exception\TestFailException;
use Unteist\Meta\TestMeta;
use Unteist\Strategy\Context;
use Unteist\TestCase;

/**
 * Class TestController
 *
 * @package Unteist\Processor\Controller
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class TestController extends ProcessorController
{
    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var float
     */
    protected $started;
    /**
     * @var int
     */
    protected $asserts;
    /**
     * @var TestCase
     */
    protected $test_case;
    /**
     * @var TestMeta
     */
    protected $test;
    /**
     * @var TestEvent
     */
    protected $event;
    /**
     * @var array
     */
    protected $data_set;

    /**
     * @param Context $context
     * @param EventDispatcherInterface $dispatcher
     * @param LoggerInterface $logger
     * @param TestCase $test_case
     * @param TestMeta $test
     * @param array $data_set
     */
    public function __construct(
        Context $context,
        EventDispatcherInterface $dispatcher,
        LoggerInterface $logger,
        TestCase $test_case,
        TestMeta $test,
        array $data_set
    ) {
        $this->dispatcher = $dispatcher;
        $this->logger = $logger;
        $this->test_case = $test_case;
        $this->test = $test;
        $this->data_set = $data_set;
        parent::__construct($context);
    }

    /**
     * Controller behavior.
     *
     * @throws TestFailException
     * @return int Status code
     */
    protected function behavior()
    {
        $this->started = microtime(true);
        $this->asserts = Assert::getAssertsCount();
        call_user_func_array([$this->test_case, $this->test->getMethod()], $this->data_set);
        if ($this->test->getExpectedException()) {
            throw new TestFailException('Expected exception ' . $this->test->getExpectedException());
        }
        $this->finish($this->test, $this->event, TestMeta::TEST_DONE);

        return 0;
    }

    /**
     * Do all dirty job after test is finish.
     *
     * @param TestMeta $test Meta description of test
     * @param TestEvent $event Test event
     * @param int $status Test status
     * @param \Exception $e
     */
    protected function finish(TestMeta $test, TestEvent $event, $status, \Exception $e = null)
    {
        $test->setStatus($status);
        $event->setStatus($status);
        $event->setDepends($test->getDependencies());
        $event->setTime(floatval(microtime(true) - $this->started));
        $event->setAsserts(Assert::getAssertsCount() - $this->asserts);
        if ($status === TestMeta::TEST_DONE) {
            $context = [];
        } else {
            $event->setException($e);
            $context = [
                'pid' => getmypid(),
                'test' => $test->getMethod(),
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ];
        }
        switch ($status) {
            case TestMeta::TEST_DONE:
                $this->dispatcher->dispatch(EventStorage::EV_TEST_SUCCESS, $event);
                break;
            case TestMeta::TEST_SKIPPED:
                $this->logger->debug('The test was skipped.', $context);
                $this->dispatcher->dispatch(EventStorage::EV_TEST_SKIPPED, $event);
                break;
            case TestMeta::TEST_FAILED:
                $this->logger->debug('Assert fail.', $context);
                $this->dispatcher->dispatch(EventStorage::EV_TEST_FAIL, $event);
                break;
            case TestMeta::TEST_INCOMPLETE:
                $this->logger->debug('Test incomplete.', $context);
                $this->dispatcher->dispatch(EventStorage::EV_TEST_INCOMPLETE, $event);
                break;
            default:
                $this->logger->critical('Unexpected exception.', $context);
                $this->dispatcher->dispatch(EventStorage::EV_TEST_ERROR, $event);
        }
    }

    /**
     * Controller behavior on SkipTestException.
     *
     * @param SkipTestException $e
     *
     * @return int Status code
     */
    protected function onSkip(SkipTestException $e)
    {
        $this->finish($this->test, $this->event, TestMeta::TEST_SKIPPED, $e);

        return 1;
    }

    /**
     * Controller behavior on TestFailException.
     *
     * @param TestFailException $e
     *
     * @return void
     */
    protected function onFailure(TestFailException $e)
    {
        $this->finish($this->test, $this->event, TestMeta::TEST_FAILED, $e);
    }

    /**
     * Controller behavior on TestErrorException.
     *
     * @param TestErrorException $e
     *
     * @return void
     */
    protected function onError(TestErrorException $e)
    {
        $this->finish($this->test, $this->event, TestMeta::TEST_FAILED, $e);
    }

    /**
     * Controller behavior on Incomplete exception.
     *
     * @param IncompleteTestException $e
     *
     * @return void
     */
    protected function onIncomplete(IncompleteTestException $e)
    {
        $this->finish($this->test, $this->event, TestMeta::TEST_INCOMPLETE, $e);
    }

    /**
     * Controller behavior on unexpected exception.
     *
     * @param \Exception $e
     *
     * @return int Status code
     */
    protected function onException(\Exception $e)
    {
        return $this->exceptionControl($this->test, $e);
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
