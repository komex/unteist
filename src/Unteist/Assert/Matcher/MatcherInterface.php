<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Assert\Matcher;

use Unteist\Exception\AssertFailException;


/**
 * Class MatcherInterface
 *
 * @package Unteist\Assert\MatcherInterface
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
interface MatcherInterface
{
    /**
     * @param mixed $actual
     * @param string $message
     *
     * @throws AssertFailException
     */
    public function match($actual, $message = '');
}