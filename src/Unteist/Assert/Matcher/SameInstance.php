<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Assert\Matcher;


use Unteist\Assert\Assert;

/**
 * Class SameInstance
 *
 * @package Unteist\Assert\Matcher
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class SameInstance extends AbstractMatcher
{
    /**
     * Matcher condition.
     *
     * @param mixed $actual
     *
     * @return bool
     */
    public function condition($actual)
    {
        return $actual instanceof $this->expected;
    }

    /**
     * Get name of matcher.
     *
     * @return string
     */
    public function getName()
    {
        return 'SameInstance';
    }
}