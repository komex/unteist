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
     * @var AbstractMatcher
     */
    protected $expected;

    /**
     * @param AbstractMatcher $expected
     */
    public function __construct(AbstractMatcher $expected)
    {
        $this->expected = $expected;
    }

    /**
     * @inheritdoc
     */
    protected function condition($actual)
    {
        return !$this->expected->condition($actual);
    }

    /**
     * Get description for error output.
     *
     * @param mixed $actual
     *
     * @return string
     */
    protected function getFailDescription($actual)
    {
        return str_replace(
            [
                'contains ',
                'exists',
                'has ',
                'is ',
                'are ',
                'matches ',
                'starts with ',
                'ends with ',
                'reference ',
                'not not '
            ],
            [
                'does not contain ',
                'does not exist',
                'does not have ',
                'is not ',
                'are not ',
                'does not match ',
                'starts not with ',
                'ends not with ',
                'don\'t reference ',
                'not '
            ],
            $this->expected->getFailDescription($actual)
        );
    }
}
