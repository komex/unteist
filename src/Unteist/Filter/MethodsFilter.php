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
     * @var bool
     */
    private $annotation_test = false;

    /**
     * @inheritdoc
     */
    public function condition(\ReflectionMethod $method)
    {
        if ($method->isPublic() && !($method->isAbstract() || $method->isConstructor() || $method->isDestructor())) {
            if ($this->annotation_test || (strlen($method->name) > 4 && substr($method->name, 0, 4) === 'test')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Set
     * @param array $modifiers
     */
    public function setModifiers(array $modifiers)
    {
        $this->annotation_test = isset($modifiers['test']);
    }

    /**
     * Get name of this methods filter.
     *
     * @return string
     */
    public function getName()
    {
        return 'named';
    }

    /**
     * Get tests parameters.
     *
     * @param array $config
     */
    public function setParams(array $config)
    {
    }
}
