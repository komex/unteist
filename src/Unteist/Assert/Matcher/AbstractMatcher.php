<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Assert\Matcher;

use Unteist\Exception\TestFailException;
use Unteist\TestCase;

/**
 * Class AbstractMatcher
 *
 * @package Unteist\Assert\Matcher
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
abstract class AbstractMatcher
{
    /**
     * @param mixed $actual
     * @param string $message
     *
     * @throws TestFailException
     */
    final public function match($actual, $message = '')
    {
        if (!$this->condition($actual)) {
            $this->fail($actual, $message);
        }
    }

    /**
     * Matcher condition.
     *
     * @param mixed $actual
     *
     * @return bool
     */
    abstract protected function condition($actual);

    /**
     * @param mixed $actual
     * @param string $message
     *
     * @throws TestFailException
     */
    protected function fail($actual, $message)
    {
        $formatted = (empty($message) ? '' : $message . PHP_EOL);
        TestCase::markAsFail($formatted);
    }
}
