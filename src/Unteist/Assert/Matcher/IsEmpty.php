<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Assert\Matcher;

/**
 * Class IsEmpty
 *
 * @package Unteist\Assert\Matcher
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class IsEmpty extends AbstractMatcher
{
    /**
     * Matcher condition.
     *
     * @param mixed $actual
     *
     * @return bool
     */
    protected function condition($actual)
    {
        return empty($actual);
    }

    /**
     * @inheritdoc
     */
    protected function getFailDescription($actual)
    {
        return $this->export($actual) . ' is empty';
    }
}
