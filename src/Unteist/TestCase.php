<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Unteist\Event\TestEvent;

/**
 * Class TestCase
 *
 * @package Unteist
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class TestCase
{
    /**
     * @var \ArrayObject
     */
    protected $local_storage;
    /**
     * @var ContainerBuilder
     */
    private $config;
    /**
     * @var \ArrayObject
     */
    private $global_storage;

    /**
     * Create a new TestCase.
     */
    public function __construct()
    {
        $this->local_storage = new \ArrayObject();
    }

    /**
     * Get global storage.
     *
     * @return \ArrayObject
     */
    public function getGlobalStorage()
    {
        return $this->global_storage;
    }

    /**
     * Set global storage for variables.
     *
     * @param \ArrayObject $global_storage Global variables
     */
    public function setGlobalStorage(\ArrayObject $global_storage)
    {
        $this->global_storage = $global_storage;
    }

    /**
     * Set project configuration.
     *
     * @param ContainerBuilder $config Project configuration
     */
    public function setConfig(ContainerBuilder $config)
    {
        $this->config = $config;
    }

    /**
     * Get parameter from global config.
     *
     * @param string $name Parameter name
     *
     * @return mixed
     */
    public function getParameter($name)
    {
        return $this->config->getParameter($name);
    }

    /**
     * Get service by its name.
     *
     * @param string $name Service name
     *
     * @return object
     */
    public function getService($name)
    {
        return $this->config->get($name);
    }
}