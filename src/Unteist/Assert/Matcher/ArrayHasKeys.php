<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Assert\Matcher;

/**
 * Class ArrayHasKeys
 *
 * @package Unteist\Assert\Matcher
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class ArrayHasKeys extends AbstractMatcher
{
    /**
     * @param array $expected
     */
    public function __construct(array $expected)
    {
        parent::__construct($expected);
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'ArrayHasKeys';
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
        foreach ($this->expected as $expected) {
            if (!array_key_exists($expected, $actual)) {
                return false;
            }
        }

        return true;
    }
}
