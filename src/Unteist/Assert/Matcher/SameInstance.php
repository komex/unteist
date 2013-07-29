<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Assert\Matcher;


use SebastianBergmann\Diff;
use Unteist\Assert\Assert;
use Unteist\Exception\AssertFailException;

/**
 * Class SameInstance
 *
 * @package Unteist\Assert\Matcher
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class SameInstance implements MatcherInterface
{
    /**
     * @var string
     */
    protected $expected;

    /**
     * @param string $expected
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
    public function match($actual, $message = '')
    {
        if (!($actual instanceof $this->expected)) {
            $formatted = (empty($message) ? '' : $message) . PHP_EOL;
            Assert::fail($formatted);
        }
    }
}