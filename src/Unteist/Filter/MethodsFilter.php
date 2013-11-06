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
    private $isTest = false;

    /**
     * @inheritdoc
     */
    public function condition(\ReflectionMethod $method)
    {
        if ($method->isPublic() && !($method->isAbstract() || $method->isConstructor() || $method->isDestructor())) {
            if ($this->isTest || (strlen($method->name) > 4 && substr($method->name, 0, 4) === 'test')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Set method's annotations to filter.
     *
     * @param array $annotations
     */
    public function setAnnotations(array $annotations)
    {
        $this->isTest = array_key_exists('test', $annotations);
    }

    /**
     * Set global configuration.
     *
     * @param array $config
     */
    public function setConfig(array $config)
    {

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
}
