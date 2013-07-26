<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Assert\Constraint;

use SebastianBergmann\Exporter\Exporter;


/**
 * Class IsNull
 *
 * @package Unteist\Assert\Constraint
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class IsNull implements ConstraintInterface
{
    /**
     * @var mixed
     */
    protected $element;

    /**
     * @param mixed $element
     */
    public function __construct($element)
    {
        $this->element = $element;
    }

    /**
     * Check conditions.
     *
     * @return bool
     */
    public function matches()
    {
        return $this->element === null;
    }

    /**
     * Get a description of constraint.
     *
     * @return string
     */
    public function toString()
    {
        $exporter = new Exporter();

        return $exporter->export($this->element) . ' is null';
    }
}