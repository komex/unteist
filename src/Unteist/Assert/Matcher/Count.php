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
     * @var int
     */
    private $count;

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
            $this->count = count($actual);
        } elseif ($actual instanceof \Traversable) {
            $this->count = iterator_count($actual);
        } else {
            throw new \InvalidArgumentException(
                'Actual variable must be an instance of Countable, Traversable or an array.'
            );
        }

        return $this->count === $this->expected;
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
        return sprintf('actual size %d matches expected size %d', $this->count, $this->expected);
    }
}
