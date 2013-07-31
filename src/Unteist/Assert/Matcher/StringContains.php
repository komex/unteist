<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Assert\Matcher;


/**
 * Class StringContains
 *
 * @package Unteist\Assert\Matcher
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class StringContains extends AbstractMatcher
{
    /**
     * Get name of matcher.
     *
     * @return string
     */
    public function getName()
    {
        return 'StringContains';
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
        return strpos($actual, $this->expected) !== false;
    }
}
