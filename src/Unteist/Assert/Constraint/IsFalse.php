<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Assert\Constraint;

use SebastianBergmann\Exporter\Exporter;


/**
 * Class IsFalse
 *
 * @package Unteist\Assert\Constraint
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class IsFalse implements ConstraintInterface
{
    /**
     * @var mixed
     */
    protected $element;
    /**
     * @var Exporter
     */
    protected $exporter;

    /**
     * @param mixed $element
     */
    public function __construct($element)
    {
        $this->element = $element;
        $this->exporter = new Exporter();
    }

    /**
     * Check conditions.
     *
     * @return bool
     */
    public function matches()
    {
        return $this->element === false;
    }

    /**
     * Get a description of constraint.
     *
     * @return string
     */
    public function toString()
    {
        return $this->exporter->export($this->element) . ' is false';
    }
}