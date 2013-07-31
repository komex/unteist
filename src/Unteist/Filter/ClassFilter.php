<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Filter;

/**
 * Class ClassFilter
 *
 * @package Unteist\Filter
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class ClassFilter implements ClassFilterInterface
{
    /**
     * Filter classes.
     *
     * @param \ReflectionClass $class Class to filter.
     *
     * @return bool Can we use this class?
     */
    public function filter(\ReflectionClass $class)
    {
        $name = $class->getShortName();

        return (!$class->isAbstract() && !$class->isInterface() && strlen($name) > 4 && substr($name, -4) === 'Test');
    }

    /**
     * Get name of this class filter.
     *
     * @return string
     */
    public function getName()
    {
        return 'named';
    }
}
