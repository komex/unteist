<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Assert\Matcher;


/**
 * Class AllOf
 *
 * @package Unteist\Assert\Matcher
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class AllOf extends AbstractMatcher
{
    /**
     * @param AbstractMatcher[] $expected
     */
    public function __construct(array $expected)
    {
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
        /** @var AbstractMatcher $expected */
        foreach ($this->expected as $expected) {
            if ($expected->condition($actual) === false) {
                return false;
            }
        }

        return true;
    }
}