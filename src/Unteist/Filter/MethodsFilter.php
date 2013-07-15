<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Filter;

/**
 * Class MethodsFilter
 *
 * @package Unteist\Filter
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
final class MethodsFilter extends AbstractMethodsFilter
{
    /**
     * Condition for filter test methods.
     *
     * @param \ReflectionMethod $method Method to check
     *
     * @return bool Is it right method?
     */
    public function condition(\ReflectionMethod $method)
    {
        return ($method->isPublic() && !($method->isAbstract() || $method->isConstructor() || $method->isDestructor())
            && strlen($method->name) > 4 && substr($method->name, 0, 4) === 'test');
    }
}