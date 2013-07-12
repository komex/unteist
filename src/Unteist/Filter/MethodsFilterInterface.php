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
     * Filter TestCase methods.
     *
     * @param \ReflectionMethod[] $methods Methods to filter
     *
     * @return \ReflectionMethod[]
     */
    public function filter(array $methods);
}