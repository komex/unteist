<?php
/**
 * This file is a part of Unteist project.
 *
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Filter;

/**
 * Class ClassFilterInterface
 *
 * @package Unteist\Filter
 */
interface ClassFilterInterface
{
    /**
     * Filter classes.
     *
     * @param \ReflectionClass $class Class to filter.
     *
     * @return bool Can we use this class?
     */
    public function filter(\ReflectionClass $class);
}