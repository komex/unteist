<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Filter;

/**
 * Class GroupMethodsFilter
 *
 * @package Unteist\Filter
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class GroupMethodsFilter implements MethodsFilterInterface
{
    /**
     * @var string
     */
    private $methodGroups;
    /**
     * @var array
     */
    private $neededGroups;

    /**
     * Condition for filter test methods.
     *
     * @param \ReflectionMethod $method Method to check
     *
     * @return bool Is it right method?
     */
    public function condition(\ReflectionMethod $method)
    {
        if (empty($this->methodGroups)) {
            return false;
        } else {
            $methodGroups = preg_split('/[\s,]+/', $this->methodGroups, -1, PREG_SPLIT_NO_EMPTY);

            return count(array_intersect($methodGroups, $this->neededGroups)) > 0;
        }
    }

    /**
     * Get name of this methods filter.
     *
     * @return string
     */
    public function getName()
    {
        return 'group';
    }

    /**
     * Set method's annotations to filter.
     *
     * @param array $annotations
     */
    public function setAnnotations(array $annotations)
    {
        if (isset($annotations['groups'])) {
            $this->methodGroups = $annotations['groups'];
        }
    }

    /**
     * Set global configuration.
     *
     * @param array $config
     *
     * @throws \InvalidArgumentException
     */
    public function setConfig(array $config)
    {
        if (empty($config['groups']) or !is_array($config['groups'])) {
            throw new \InvalidArgumentException('The list of needed groups does not specified in configuration.');
        } else {
            $this->neededGroups = $config['groups'];
        }
    }
}
