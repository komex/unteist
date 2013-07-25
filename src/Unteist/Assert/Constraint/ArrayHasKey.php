<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Assert\Constraint;


/**
 * Class ArrayHasKey
 *
 * @package Unteist\Assert\Constraint
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class ArrayHasKey implements ConstraintInterface
{
    /**
     * @var int|string
     */
    protected $key;
    /**
     * @var array
     */
    protected $array;

    /**
     * @param int|string $key
     * @param array $array
     *
     * @throws \InvalidArgumentException If key is not a string or a number.
     */
    public function __construct($key, array $array)
    {
        if (!is_int($key) && !is_string($key)) {
            throw new \InvalidArgumentException('The key must be a string or a number.');
        }
        $this->key = $key;
        $this->array = $array;
    }

    /**
     * Check conditions.
     *
     * @return bool
     */
    public function matches()
    {
        return array_key_exists($this->key, $this->array);
    }

    /**
     * Get a description of failure.
     *
     * @return string
     */
    public function failureDescription()
    {
        return 'an array ' . $this->toString();
    }

    /**
     * Get a description of constraint.
     *
     * @return string
     */
    public function toString()
    {
        return 'has the key ' . $this->key;
    }
}