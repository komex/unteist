<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Filter;

use Unteist\Exception\FilterException;

/**
 * Class ClassFilterInterface
 *
 * @package Unteist\Filter
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
interface ClassFilterInterface
{
    /**
     * Filter classes.
     *
     * @param \ReflectionClass $class Class to filter.
     *
     * @throws FilterException
     */
    public function filter(\ReflectionClass $class);

    /**
     * Get name of this class filter.
     *
     * @return string
     */
    public function getName();
}
