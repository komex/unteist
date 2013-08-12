<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Filter;

use Unteist\Processor\Runner;

/**
 * Class GroupMethodsFilter
 *
 * @package Unteist\Filter
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class GroupMethodsFilter implements MethodsFilterInterface
{
    /**
     * @var array
     */
    private $groups;

    /**
     * Condition for filter test methods.
     *
     * @param \ReflectionMethod $method Method to check
     *
     * @return bool Is it right method?
     */
    public function condition(\ReflectionMethod $method)
    {
        if (empty($this->groups)) {
            return true;
        } else {
            $annotation = Runner::parseDocBlock($method->getDocComment(), ['group']);

            return (!empty($annotation['group']) && in_array($annotation['group'], $this->groups));
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
     * Get tests parameters.
     *
     * @param array $config
     */
    public function setParams(array $config)
    {
        if (!empty($config['groups'])) {
            $this->groups = $config['groups'];
        }
    }
}
