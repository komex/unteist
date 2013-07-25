<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Assert\Constraint;


/**
 * Class Count
 *
 * @package Unteist\Assert\Constraint
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class Count implements ConstraintInterface
{
    /**
     * @var int
     */
    protected $expected;
    /**
     * @var int
     */
    protected $count;

    /**
     * @param int $expected Expected count value
     * @param array|\Countable|\Traversable $element Element to check
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($expected, $element)
    {
        if (!is_numeric($expected)) {
            throw new \InvalidArgumentException('Expected count must be a numeric');
        }
        $this->expected = intval($expected, 10);
        $this->count = $this->getCountOf($element);
        if ($this->count === null) {
            throw new \InvalidArgumentException('Check item must be an array, Countable or Traversable.');
        }
    }

    /**
     * Get count of element.
     *
     * @param array|\Countable|\Traversable $element
     *
     * @return int|null
     */
    protected function getCountOf($element)
    {
        if ($element instanceof \Countable || is_array($element)) {
            return count($element);
        } else {
            if ($element instanceof \Traversable) {
                return iterator_count($element);
            }
        }

        return null;
    }

    /**
     * Check conditions.
     *
     * @return bool
     */
    public function matches()
    {
        return $this->count === $this->expected;
    }

    /**
     * Get a description of constraint.
     *
     * @return string
     */
    public function toString()
    {
        return 'count matches';
    }

    /**
     * Get a description of failure.
     *
     * @return string
     */
    public function failureDescription()
    {
        return sprintf('actual size %d matches expected size %d', $this->count, $this->expected);
    }
}