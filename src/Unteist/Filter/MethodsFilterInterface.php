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
     *
     * @return bool Is it right method?
     */
    public function condition(\ReflectionMethod $method);

    /**
     * Get name of this methods filter.
     *
     * @return string
     */
    public function getName();

    /**
     * Set method's annotations to filter.
     *
     * @param array $annotations
     */
    public function setAnnotations(array $annotations);
}
