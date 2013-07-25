<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Assert\Constraint;

use SebastianBergmann\Exporter\Exporter;


/**
 * Class IsInstanceOf
 *
 * @package Unteist\Assert\Constraint
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class IsInstanceOf implements ConstraintInterface
{
    /**
     * @var string
     */
    protected $class_name;
    /**
     * @var Object
     */
    protected $class;
    /**
     * @var bool
     */
    protected $inverse;

    /**
     * @param Object $class
     * @param string $class_name
     * @param bool $inverse
     */
    public function __construct($class, $class_name, $inverse = false)
    {
        $this->class = $class;
        $this->class_name = $class_name;
        $this->inverse = $inverse;
    }

    /**
     * Check conditions.
     *
     * @return bool
     */
    public function matches()
    {
        return ($this->class instanceof $this->class_name);
    }

    /**
     * Get a description of constraint.
     *
     * @return string
     */
    public function toString()
    {
        $exporter = new Exporter();

        return sprintf(
            '%s is%s an instance of "%s"',
            $exporter->shortenedExport($this->class),
            ($this->inverse ? ' not' : ''),
            $this->class_name
        );
    }

} 