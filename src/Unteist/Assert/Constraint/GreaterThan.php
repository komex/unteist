<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Assert\Constraint;

use SebastianBergmann\Exporter\Exporter;


/**
 * Class GreaterThan
 *
 * @package Unteist\Assert\Constraint
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class GreaterThan implements ConstraintInterface
{
    /**
     * @var mixed
     */
    protected $more;
    /**
     * @var mixed
     */
    protected $less;

    /**
     * @param mixed $more
     * @param mixed $less
     */
    public function __construct($more, $less)
    {
        $this->more = $more;
        $this->less = $less;
    }

    /**
     * Check conditions.
     *
     * @return bool
     */
    public function matches()
    {
        return $this->more > $this->less;
    }

    /**
     * Get a description of constraint.
     *
     * @return string
     */
    public function toString()
    {
        $exporter = new Exporter();

        return $exporter->export($this->more) . ' is greater than ' . $exporter->export($this->less);
    }
}