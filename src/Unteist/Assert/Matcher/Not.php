<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Assert\Matcher;


/**
 * Class Not
 *
 * @package Unteist\Assert\Matcher
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 * @property AbstractMatcher $expected
 */
class Not extends AbstractMatcher
{
    /**
     * @param AbstractMatcher $expected
     */
    public function __construct(AbstractMatcher $expected)
    {
        parent::__construct($expected);
    }

    /**
     * Get name of matcher.
     *
     * @return string
     */
    public function getName()
    {
        return 'not ' . $this->expected->getName();
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
        return !$this->expected->condition($actual);
    }
}