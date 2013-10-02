<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Processor\Controller;

use Unteist\Exception\IncompleteTestException;
use Unteist\Exception\SkipTestException;
use Unteist\Exception\TestErrorException;
use Unteist\Exception\TestFailException;
use Unteist\Strategy\Context;

/**
 * Class ProcessorController
 *
 * @package Unteist\Processor\Controller
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
abstract class ProcessorController
{
    /**
     * @var Context
     */
    private $context;

    /**
     * @param Context $context
     */
    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    /**
     * @return int
     */
    public function run()
    {
        try {
            return $this->convert();
        } catch (SkipTestException $e) {
            $status_code = $this->onSkip($e);
        } catch (TestFailException $e) {
            $this->onFailure($e);
            $status_code = $this->context->onFailure($e);
        } catch (IncompleteTestException $e) {
            $this->onIncomplete($e);
            $status_code = $this->context->onIncomplete($e);
        }

        return $status_code;
    }

    /**
     * Controller behavior.
     *
     * @return int Status code
     */
    abstract protected function behavior();

    /**
     * Controller behavior on SkipTestException.
     *
     * @param SkipTestException $e
     *
     * @return int Status code
     */
    abstract protected function onSkip(SkipTestException $e);

    /**
     * Controller behavior on TestFailException.
     *
     * @param TestFailException $e
     *
     * @return void
     */
    abstract protected function onFailure(TestFailException $e);

    /**
     * Controller behavior on TestErrorException.
     *
     * @param TestErrorException $e
     *
     * @return void
     */
    abstract protected function onError(TestErrorException $e);

    /**
     * Controller behavior on Incomplete exception.
     *
     * @param IncompleteTestException $e
     *
     * @return void
     */
    abstract protected function onIncomplete(IncompleteTestException $e);

    /**
     * Controller behavior on unexpected exception.
     *
     * @param \Exception $e
     *
     * @return int Status code
     */
    abstract protected function onException(\Exception $e);

    /**
     * Convert exceptions using context.
     *
     * @return int Status code
     */
    private function convert()
    {
        try {
            return $this->behavior();
        } catch (TestFailException $e) {
            $status_code = $this->context->onFailure($e);
            $this->onFailure($e);
        } catch (TestErrorException $e) {
            $status_code = $this->context->onError($e);
            $this->onError($e);
        } catch (IncompleteTestException $e) {
            $status_code = $this->context->onIncomplete($e);
            $this->onIncomplete($e);
        } catch (\Exception $e) {
            $status_code = $this->onException($e);
        }

        return $status_code;
    }
}
