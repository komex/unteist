<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Assert\Matcher;


/**
 * Class ArrayHasKey
 *
 * @package Unteist\Assert\Matcher
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class ArrayHasKey extends AbstractMatcher
{
    /**
     * Get name of matcher.
     *
     * @return string
     */
    public function getName()
    {
        return 'ArrayHasKey';
    }

    /**
     * Matcher condition.
     *
     * @param array $actual
     *
     * @throws \InvalidArgumentException If variable is not an array
     * @return bool
     */
    protected function condition($actual)
    {
        if (!is_array($actual)) {
            throw new \InvalidArgumentException('Specified variable must be an array.');
        }

        return array_key_exists($this->expected, $actual);
    }
}