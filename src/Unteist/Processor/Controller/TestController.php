<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Processor\Controller;

use Unteist\Event\TestEvent;
use Unteist\Exception\IncompleteTestException;
use Unteist\Exception\SkipTestException;
use Unteist\Exception\TestErrorException;
use Unteist\Exception\TestFailException;
use Unteist\Meta\TestMeta;
use Unteist\Processor\Runner;
use Unteist\Strategy\Context;

/**
 * Class TestController
 *
 * @package Unteist\Processor\Controller
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class TestController extends ProcessorController
{
    /**
     * @var Runner
     */
    protected $runner;
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
     * @param Runner $runner
     * @param TestEvent $event
     * @param TestMeta $test
     * @param array $data_set
     */
    public function __construct(Context $context, Runner $runner, TestEvent $event, TestMeta $test, array $data_set)
    {
        $this->setContext($context);
        $this->setRunner($runner);
        $this->setEvent($event);
        $this->test = $test;
        $this->data_set = $data_set;
    }

    /**
     * @param TestEvent $event
     */
    protected function setEvent(TestEvent $event)
    {
        $this->event = $event;
    }

    /**
     * @param Runner $runner
     */
    protected function setRunner(Runner $runner)
    {
        $this->runner = $runner;
    }

    /**
     * Controller behavior.
     *
     * @throws TestFailException
     * @return int Status code
     */
    protected function behavior()
    {
        call_user_func_array([$this->runner->getTestCase(), $this->test->getMethod()], $this->data_set);
        if ($this->test->getExpectedException()) {
            throw new TestFailException('Expected exception ' . $this->test->getExpectedException());
        }
        $this->runner->finish($this->test, $this->event, TestMeta::TEST_DONE);

        return 0;
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
        $this->runner->finish($this->test, $this->event, TestMeta::TEST_SKIPPED, $e);

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
        $this->runner->finish($this->test, $this->event, TestMeta::TEST_FAILED, $e);
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
        $this->runner->finish($this->test, $this->event, TestMeta::TEST_FAILED, $e);
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
        $this->runner->finish($this->test, $this->event, TestMeta::TEST_INCOMPLETE, $e);
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
