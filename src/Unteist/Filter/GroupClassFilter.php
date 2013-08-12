<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <komexx@gmail.com>
 */

namespace Unteist\Filter;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Unteist\Processor\Runner;

/**
 * Class GroupClassFilter
 *
 * @package Unteist\Filter
 * @author Andrey Kolchenko <komexx@gmail.com>
 */
class GroupClassFilter implements ClassFilterInterface
{
    /**
     * @var string
     */
    private $group;

    /**
     * Filter classes.
     *
     * @param \ReflectionClass $class Class to filter.
     *
     * @return bool Can we use this class?
     */
    public function filter(\ReflectionClass $class)
    {
        if (empty($this->group)) {
            return true;
        } else {
            $annotation = Runner::parseDocBlock($class->getDocComment(), ['group']);

            return (isset($annotation['group']) && $annotation['group'] === $this->group);
        }
    }

    /**
     * Get name of this class filter.
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
        if (!empty($config['params']['filter']['group'])) {
            $this->group = trim($config['params']['filter']['group']);
        }
    }
}
