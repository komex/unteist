<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <komexx@gmail.com>
 */

namespace Unteist\Assert\Matcher;


/**
 * Class StringEndsWith
 *
 * @package Unteist\Assert\Matcher
 * @author Andrey Kolchenko <komexx@gmail.com>
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
        return 'StringStartsWith';
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