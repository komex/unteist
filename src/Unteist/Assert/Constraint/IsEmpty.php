<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Assert\Constraint;

use SebastianBergmann\Exporter\Exporter;


/**
 * Class IsEmpty
 *
 * @package Unteist\Assert\Constraint
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class IsEmpty implements ConstraintInterface
{
    /**
     * @var mixed
     */
    protected $element;
    /**
     * @var bool
     */
    protected $inverse;

    /**
     * @param mixed $element
     * @param bool $inverse
     */
    public function __construct($element, $inverse = false)
    {
        $this->element = $element;
        $this->inverse = $inverse;
    }

    /**
     * Check conditions.
     *
     * @return bool
     */
    public function matches()
    {
        return $this->inverse ? !empty($this->element) : empty($this->element);
    }

    /**
     * Get a description of constraint.
     *
     * @return string
     */
    public function toString()
    {
        $exporter = new Exporter();

        return $exporter->export($this->element) . (($this->inverse) ? ' is not empty' : ' is empty');
    }
}