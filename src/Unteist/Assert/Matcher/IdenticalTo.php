<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Assert\Matcher;

/**
 * Class IdenticalTo
 *
 * @package Unteist\Assert\MatcherInterface
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class IdenticalTo extends EqualTo
{
    /**
     * @inheritdoc
     */
    protected function condition($actual)
    {
        return $actual === $this->expected;
    }

    /**
     * @inheritdoc
     */
    protected function getFailDescription($actual)
    {
        return 'variables are identical:' . PHP_EOL . $this->getDiff($actual);
    }
}
