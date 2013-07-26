<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Assert\Constraint;


/**
 * Class StringContains
 *
 * @package Unteist\Assert\Constraint
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class StringContains implements ConstraintInterface
{
    /**
     * @var string
     */
    protected $string;
    /**
     * @var string
     */
    protected $element;
    /**
     * @var bool
     */
    protected $ignore_case;

    /**
     * @param string $string What find
     * @param string $element Where find
     * @param bool $ignore_case
     */
    function __construct($string, $element, $ignore_case = false)
    {
        $this->string = $string;
        $this->element = $element;
        $this->ignore_case = $ignore_case;
    }

    /**
     * Check conditions.
     *
     * @return bool
     */
    public function matches()
    {
        if ($this->ignore_case) {
            return stripos($this->element, $this->string) !== false;
        } else {
            return strpos($this->element, $this->string) !== false;
        }
    }

    /**
     * Get a description of constraint.
     *
     * @return string
     */
    public function toString()
    {
        if ($this->ignore_case) {
            $string = strtolower($this->string);
        } else {
            $string = $this->string;
        }

        return sprintf('contains "%s"', $string);
    }
}