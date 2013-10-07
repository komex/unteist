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
use Unteist\Event\TestCaseEvent;
use Unteist\Event\TestEvent;
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
class RunTestsController extends SkipTestsController
{
    /**
     * @var EventDispatcherInterface
     */
    protected $precondition;
    /**
     * @var Runner
     */
    protected $runner;
    /**
     * @var float
     */
    protected $started;
    /**
     * @var int
     */
    protected $asserts;
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var Context
     */
    private $context;

    /**
     * @param Runner $runner
     * @param Context $context
     * @param LoggerInterface $logger
     * @param EventDispatcherInterface $precondition
     * @param EventDispatcherInterface $dispatcher
     * @param TestCaseEvent $test_case_event
     * @param array $listeners
     */
    public function __construct(
        Runner $runner,
        Context $context,
        LoggerInterface $logger,
        EventDispatcherInterface $precondition,
        EventDispatcherInterface $dispatcher,
        TestCaseEvent $test_case_event,
        array $listeners
    ) {
        $this->context = $context;
        $this->logger = $logger;
        $this->precondition = $precondition;
        $this->runner = $runner;
        parent::__construct($dispatcher, $test_case_event, $listeners);
    }

    /**
     * Run test.
     *
     * @param TestMeta $test
     *
     * @return int
     */
    public function run(TestMeta $test)
    {
        if ($test->getStatus() !== TestMeta::TEST_NEW && $test->getStatus() !== TestMeta::TEST_MARKED) {
            return 0;
        }
        $this->runner->resolveDependencies($test);
        $dataProvider = $this->runner->getDataSet($test->getDataProvider());
        $status_code = 0;
        foreach ($dataProvider as $dp_number => $data_set) {
            $event = new TestEvent($test->getMethod(), $this->test_case_event);
            $event->setDepends($test->getDependencies());
            if (count($dataProvider) > 1) {
                $event->setDataSet($dp_number + 1);
            }
            try {
                $this->dispatcher->dispatch(EventStorage::EV_BEFORE_TEST, $event);
                $this->precondition->dispatch(EventStorage::EV_BEFORE_TEST, $event);
            } catch (\Exception $e) {
                $this->finish($test, $event, TestMeta::TEST_SKIPPED, $e, false);
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
     * Do all dirty job after test is finish.
     *
     * @param TestMeta $test Meta description of test
     * @param TestEvent $event Test event
     * @param int $status Test status
     * @param \Exception $e
     * @param bool $send_event Send After test event.
     */
    private function finish(TestMeta $test, TestEvent $event, $status, \Exception $e = null, $send_event = true)
    {
        $test->setStatus($status);
        $event->setStatus($status);
        $event->setDepends($test->getDependencies());
        $event->setTime(floatval(microtime(true) - $this->started));
        $event->setAsserts(Assert::getAssertsCount() - $this->asserts);
        $this->asserts = Assert::getAssertsCount();
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
        $this->afterTest($event, $send_event);
    }

    /**
     * Test done. Generate EV_AFTER_TEST event.
     *
     * @param TestEvent $event
     * @param bool $send_event
     */
    private function afterTest(TestEvent $event, $send_event)
    {
        try {
            if ($send_event) {
                $this->precondition->dispatch(EventStorage::EV_AFTER_TEST, $event);
            }
            $this->dispatcher->dispatch(EventStorage::EV_AFTER_TEST, $event);
        } catch (\Exception $e) {
            $controller = new SkipTestsController($this->dispatcher, $this->test_case_event, $this->listeners);
            $controller->setException($e);
            $this->runner->setController($controller);
        }
    }

    /**
     * @param TestMeta $test
     * @param TestEvent $event
     * @param array $data_set
     *
     * @return int
     */
    private function execute(TestMeta $test, TestEvent $event, array $data_set)
    {
        try {
            $this->started = microtime(true);
            $this->asserts = Assert::getAssertsCount();
            $status_code = $this->convert($test, $event, $data_set);
        } catch (SkipTestException $e) {
            $this->finish($test, $event, TestMeta::TEST_SKIPPED, $e);
            $status_code = 1;
        } catch (TestFailException $e) {
            $this->finish($test, $event, TestMeta::TEST_FAILED, $e);
            $status_code = $this->context->onFailure($e);
        } catch (IncompleteTestException $e) {
            $this->finish($test, $event, TestMeta::TEST_INCOMPLETE, $e);
            $status_code = $this->context->onIncomplete($e);
        }

        return $status_code;
    }

    /**
     * Controller behavior.
     *
     * @param TestMeta $test
     * @param TestEvent $event
     * @param array $data_set
     *
     * @throws TestFailException
     * @return int Status code
     */
    private function behavior(TestMeta $test, TestEvent $event, array $data_set)
    {
        call_user_func_array([$this->runner->getTestCase(), $test->getMethod()], $data_set);
        if ($test->getExpectedException()) {
            throw new TestFailException('Expected exception ' . $test->getExpectedException());
        }
        $this->finish($test, $event, TestMeta::TEST_DONE);

        return 0;
    }

    /**
     * Convert exceptions using context.
     *
     * @param TestMeta $test
     * @param TestEvent $event
     * @param array $data_set
     *
     * @return int Status code
     */
    private function convert(TestMeta $test, TestEvent $event, array $data_set)
    {
        try {
            $status_code = $this->behavior($test, $event, $data_set);
        } catch (TestFailException $e) {
            $status_code = $this->context->onFailure($e);
            $this->finish($test, $event, TestMeta::TEST_FAILED, $e);
        } catch (TestErrorException $e) {
            $status_code = $this->context->onError($e);
            $this->finish($test, $event, TestMeta::TEST_FAILED, $e);
        } catch (IncompleteTestException $e) {
            $status_code = $this->context->onIncomplete($e);
            $this->finish($test, $event, TestMeta::TEST_INCOMPLETE, $e);
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
