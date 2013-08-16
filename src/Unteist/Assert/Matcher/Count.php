<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Assert\Matcher;

/**
 * Class Count
 *
 * @package Unteist\Assert\Matcher
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class Count extends AbstractMatcher
{
    /**
     * @var int
     */
    protected $expected;

    /**
     * @param int $expected
     */
    public function __construct($expected)
    {
        $this->expected = intval($expected, 10);
    }

    /**
     * Matcher condition.
     *
     * @param array|\Countable|\Traversable $actual
     *
     * @throws \InvalidArgumentException If actual variable has an invalid type
     * @return bool
     */
    protected function condition($actual)
    {
        if (is_array($actual) || $actual instanceof \Countable) {
            $count = count($actual);
        } elseif ($actual instanceof \Traversable) {
            $count = iterator_count($actual);
        } else {
            throw new \InvalidArgumentException(
                'Actual variable must be an instance of Countable, Traversable or an array.'
            );
        }

        return $count === $this->expected;
    }
}
