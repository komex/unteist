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
     * @inheritdoc
     */
    public function getName()
    {
        return 'StringContains';
    }

    /**
     * @inheritdoc
     */
    protected function condition($actual)
    {
        return strpos($actual, $this->expected) !== false;
    }
}
