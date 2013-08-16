<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Assert\Matcher;

/**
 * Class StringStartsWith
 *
 * @package Unteist\Assert\Matcher
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class StringStartsWith extends AbstractMatcher
{
    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'StringStartsWith';
    }

    /**
     * @inheritdoc
     */
    protected function condition($actual)
    {
        return strpos($actual, $this->expected) === 0;
    }
}
