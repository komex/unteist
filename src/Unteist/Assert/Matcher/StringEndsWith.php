<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Assert\Matcher;


/**
 * Class StringEndsWith
 *
 * @package Unteist\Assert\Matcher
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class StringEndsWith extends AbstractMatcher
{
    /**
     * Get name of matcher.
     *
     * @return string
     */
    public function getName()
    {
        return 'StringEndsWith';
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
        return substr($actual, -strlen($this->expected)) === $this->expected;
    }
}
