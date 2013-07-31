<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Assert\Matcher;

use Unteist\Assert\Assert;

/**
 * Class IdenticalTo
 *
 * @package Unteist\Assert\MatcherInterface
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class IdenticalTo extends EqualTo
{
    /**
     * Get name of matcher.
     *
     * @return string
     */
    public function getName()
    {
        return 'IdenticalTo';
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
        return $actual === $this->expected;
    }
}
