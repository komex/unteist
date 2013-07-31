<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Assert\Matcher;


use Unteist\Assert\Assert;

/**
 * Class TypeOf
 *
 * @package Unteist\Assert\Matcher
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class TypeOf extends AbstractMatcher
{

    /**
     * @param string $expected
     */
    public function __construct($expected)
    {
        $expected = strtolower($expected);
        parent::__construct($expected);
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
        return gettype($actual) == $this->expected;
    }
}