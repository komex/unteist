<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Filter;

use Unteist\Exception\FilterException;

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
     * @throws FilterException
     */
    public function filter(\ReflectionClass $class)
    {
        $name = $class->getShortName();
        if ($class->isAbstract() || $class->isInterface() || !(strlen($name) > 4 && substr($name, -4) === 'Test')) {
            throw new FilterException(sprintf('TestCase was filtered by "%s" filter.', $this->getName()));
        }
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
