<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Assert\Constraint;

use SebastianBergmann\Exporter\Exporter;


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
     * @var bool
     */
    protected $inverse;
    /**
     * @var Exporter
     */
    protected $exporter;

    /**
     * @param int|string $key
     * @param array $array
     * @param bool $inverse
     *
     * @throws \InvalidArgumentException If key is not a string or a number.
     */
    public function __construct($key, array $array, $inverse = false)
    {
        if (!is_int($key) && !is_string($key)) {
            throw new \InvalidArgumentException('The key must be a string or a number.');
        }
        $this->key = $key;
        $this->array = $array;
        $this->inverse = $inverse;
        $this->exporter = new Exporter();
    }

    /**
     * Check conditions.
     *
     * @return bool
     */
    public function matches()
    {
        $match = array_key_exists($this->key, $this->array);
        if ($this->inverse) {
            $match = !$match;
        }

        return $match;
    }

    /**
     * Get a description of constraint.
     *
     * @return string
     */
    public function toString()
    {
        return sprintf(
            'an array %s has%s the key %s',
            $this->exporter->export($this->array),
            $this->inverse ? ' not' : '',
            $this->key
        );
    }
}