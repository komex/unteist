<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <komexx@gmail.com>
 */

namespace Unteist\Filter;

/**
 * Class AbstractMethodsFilter
 *
 * @package Unteist\Filter
 * @author Andrey Kolchenko <komexx@gmail.com>
 */
abstract class AbstractMethodsFilter
{
    /**
     * Condition for filter test methods.
     *
     * @param \ReflectionMethod $method Method to check
     *
     * @return bool Is it right method?
     */
    abstract public function condition(\ReflectionMethod $method);

    /**
     * Filter TestCase methods.
     *
     * @param \ReflectionMethod[] $methods Methods to filter
     *
     * @return \ReflectionMethod[]
     */
    public function filter(array $methods)
    {
        return array_filter($methods, array($this, 'condition'));
    }
}