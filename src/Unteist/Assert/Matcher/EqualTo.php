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
 * Class EqualTo
 *
 * @package Unteist\Assert\MatcherInterface
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class EqualTo extends AbstractMatcher
{
    /**
     * Get name of matcher.
     *
     * @return string
     */
    public function getName()
    {
        return 'EqualTo';
    }

    /**
     * Matcher condition.
     *
     * @param mixed $actual
     *
     * @return bool
     */
    protected function condition($actual)
    {
        return $actual == $this->expected;
    }

    /**
     * @param mixed $actual
     * @param string $message
     *
     * @throws AssertFailException
     */
    protected function fail($actual, $message)
    {
        $formatted = (empty($message) ? '' : $message) . PHP_EOL;
        $diff = new Diff('--- Original' . PHP_EOL . '+++ Expected' . PHP_EOL);
        $formatted .= $diff->diff(var_export($actual, true), var_export($this->expected, true));
        Assert::fail($formatted);
    }
}