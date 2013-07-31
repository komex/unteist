<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Assert\Matcher;

use Unteist\Assert\Assert;
use Unteist\Exception\AssertFailException;


/**
 * Class AbstractMatcher
 *
 * @package Unteist\Assert\Matcher
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
abstract class AbstractMatcher
{
    /**
     * @var mixed
     */
    protected $expected;

    /**
     * @param mixed $expected
     */
    public function __construct($expected)
    {
        $this->expected = $expected;
    }

    /**
     * @param mixed $actual
     * @param string $message
     *
     * @throws AssertFailException
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
     * @throws AssertFailException
     */
    protected function fail($actual, $message)
    {
        $formatted = (empty($message) ? '' : $message . PHP_EOL);
        Assert::fail($formatted);
    }

    /**
     * Get name of matcher.
     *
     * @return string
     */
    abstract public function getName();
}