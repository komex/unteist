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
final class MethodsFilter implements MethodsFilterInterface
{
    /**
     * @inheritdoc
     */
    public function condition(\ReflectionMethod $method, array $modifiers)
    {
        if ($method->isPublic() && !($method->isAbstract() || $method->isConstructor() || $method->isDestructor())) {
            if (isset($modifiers['test']) || (strlen($method->name) > 4 && substr($method->name, 0, 4) === 'test')) {
                return true;
            }
        }

        return false;
    }
}