<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Assert\Constraint;


/**
 * Class ConstraintInterface
 *
 * @package Unteist\Assert\Constraint
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
interface ConstraintInterface
{
    /**
     * Check conditions.
     *
     * @return bool
     */
    public function matches();

    /**
     * Get a description of constraint.
     *
     * @return string
     */
    public function toString();

    /**
     * Get a description of failure.
     *
     * @return string
     */
    public function failureDescription();
} 