<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Assert\Matcher;

use Unteist\Exception\TestFailException;

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
     * Get description for error output.
     *
     * @param mixed $actual
     *
     * @return string
     */
    abstract protected function getFailDescription($actual);

    /**
     * @param mixed $actual
     * @param string $message
     *
     * @throws TestFailException
     */
    protected function fail($actual, $message)
    {
        $formatted = 'Failed asserting that ' . $this->getFailDescription($actual);
        $formatted = (empty($message) ? '' : $message . PHP_EOL) . $formatted;
        throw new TestFailException($formatted);
    }

    /**
     * Export variable for correct output.
     *
     * @param mixed $variable
     *
     * @return int|string
     */
    protected function export($variable)
    {
        if (is_string($variable)) {
            return '"' . $variable . '"';
        } elseif (is_numeric($variable)) {
            return $variable;
        } else {
            $type = gettype($variable);
            if ($type === 'object') {
                return '<' . get_class($variable) . ' object>';
            } else {
                return '<' . ucfirst($type) . ' variable>';
            }
        }
    }
}
