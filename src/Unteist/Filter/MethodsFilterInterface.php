<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <komexx@gmail.com>
 */

namespace Unteist\Filter;


/**
 * Class MethodsFilterInterface
 *
 * @package Unteist\Filter
 * @author Andrey Kolchenko <komexx@gmail.com>
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
}