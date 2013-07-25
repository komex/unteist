<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Exception;

use SebastianBergmann\Diff;


/**
 * Class ComparisonFailure
 *
 * @package Unteist\Exception
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class ComparisonFailure extends AssertFailException
{
    /**
     * Expected value of the retrieval which does not match $actual.
     *
     * @var mixed
     */
    protected $expected;
    /**
     * Actually retrieved value which does not match $expected.
     *
     * @var mixed
     */
    protected $actual;

    /**
     * Initialises with the expected value and the actual value.
     *
     * @param mixed $expected Expected value as string or array.
     * @param mixed $actual Actual value as string or array.
     * @param string $message A string which is prefixed on all returned lines in the difference output.
     */
    public function __construct($expected, $actual, $message = '')
    {
        $this->expected = $expected;
        $this->actual = $actual;
        $this->message = $message . $this->getDiff();
    }

    /**
     * @return string
     */
    protected function getDiff()
    {
        if (empty($this->actual) && empty($this->expected)) {
            return '';
        }

        $diff = new Diff("--- Expected\n+++ Actual\n");

        return $diff->diff($this->expected, $this->actual);
    }
}