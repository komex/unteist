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
 * Class IdenticalTo
 *
 * @package Unteist\Assert\MatcherInterface
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class IdenticalTo extends EqualTo
{
    /**
     * @param mixed $actual
     * @param string $message
     *
     * @throws AssertFailException
     */
    public function match($actual, $message = '')
    {
        if ($actual != $this->expected) {
            $formatted = (empty($message) ? '' : $message . PHP_EOL);
            $diff = new Diff('--- Original' . PHP_EOL . '+++ Expected' . PHP_EOL);
            $formatted .= $diff->diff(var_export($actual, true), var_export($this->expected, true));
            Assert::fail($formatted);
        }
    }
}