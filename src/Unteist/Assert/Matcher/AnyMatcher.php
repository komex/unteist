<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Assert\Matcher;

/**
 * Class AnyMatcher
 *
 * @package Unteist\Assert\Matcher
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class AnyMatcher extends AbstractMatcher
{
    /**
     * @var AbstractMatcher[]
     */
    protected $expected;

    /**
     * @param AbstractMatcher[] $expected
     */
    public function __construct(array $expected)
    {
        $this->expected = $expected;
    }

    /**
     * Matcher condition.
     *
     * @param mixed $actual
     *
     * @throws \InvalidArgumentException
     * @return bool
     */
    protected function condition($actual)
    {
        /** @var AbstractMatcher $expected */
        foreach ($this->expected as $expected) {
            if (!($expected instanceof AbstractMatcher)) {
                throw new \InvalidArgumentException('Expects only AbstractMatcher objects.');
            }
            if ($expected->condition($actual) === true) {
                return true;
            }
        }

        return false;
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
        return sprintf(
            'at least one condition of %d was successful',
            count($this->expected)
        );
    }
}
