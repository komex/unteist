<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Filter;


/**
 * Class MethodsFilterInterface
 *
 * @package Unteist\Filter
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
interface MethodsFilterInterface
{
    /**
     * Condition for filter test methods.
     *
     * @param \ReflectionMethod $method Method to check
     * @param array $modifiers Method modifiers
     *
     * @return bool Is it right method?
     */
    public function condition(\ReflectionMethod $method, array $modifiers);

    /**
     * Get name of this methods filter.
     *
     * @return string
     */
    public function getName();
}