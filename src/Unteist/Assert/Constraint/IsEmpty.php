<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Assert\Constraint;


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
        return empty($this->element);
    }

    /**
     * Get a description of failure.
     *
     * @return string
     */
    public function failureDescription()
    {
        $type = gettype($this->element);

        return sprintf(
            '%s %s %s',
            $type[0] == 'a' || $type[0] == 'o' ? 'an' : 'a',
            $type,
            $this->toString()
        );
    }

    /**
     * Get a description of constraint.
     *
     * @return string
     */
    public function toString()
    {
        return 'is empty';
    }
}