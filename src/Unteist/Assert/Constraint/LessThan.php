<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Assert\Constraint;

use SebastianBergmann\Exporter\Exporter;


/**
 * Class LessThan
 *
 * @package Unteist\Assert\Constraint
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class LessThan implements ConstraintInterface
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
     * @param mixed $less
     * @param mixed $more
     */
    public function __construct($less, $more)
    {
        $this->less = $less;
        $this->more = $more;
    }

    /**
     * Check conditions.
     *
     * @return bool
     */
    public function matches()
    {
        return $this->less < $this->more;
    }

    /**
     * Get a description of constraint.
     *
     * @return string
     */
    public function toString()
    {
        $exporter = new Exporter();

        return $exporter->export($this->less) . ' is less than ' . $exporter->export($this->more);
    }
}