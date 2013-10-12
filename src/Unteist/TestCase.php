<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Unteist\Exception\IncompleteTestException;
use Unteist\Exception\SkipTestException;
use Unteist\Exception\TestFailException;

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
     * @var EventDispatcherInterface
     */
    protected $dispatcher;
    /**
     * @var ContainerBuilder
     */
    private $container;

    /**
     * Create a new TestCase.
     */
    public function __construct()
    {
        $this->local_storage = new \ArrayObject();
    }

    /**
     * Mark test as incomplete.
     *
     * @param string $message
     *
     * @throws IncompleteTestException
     */
    public static function markAsIncomplete($message = '')
    {
        throw new IncompleteTestException($message);
    }

    /**
     * Mark test as fail.
     *
     * @param string $message
     *
     * @throws TestFailException
     */
    public static function markAsFail($message = '')
    {
        throw new TestFailException($message);
    }

    /**
     * Mark test skipped.
     *
     * @param string $message
     *
     * @throws SkipTestException
     */
    public static function skip($message = '')
    {
        throw new SkipTestException($message);
    }

    /**
     * Get global storage.
     *
     * @return \ArrayObject
     */
    public function getGlobalStorage()
    {
        return $this->container->get('storage.global');
    }

    /**
     * Set configuration container.
     *
     * @param ContainerBuilder $container Project configuration
     */
    public function setContainer(ContainerBuilder $container)
    {
        $this->container = $container;
        $this->dispatcher = $container->get('dispatcher');
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
        return $this->container->getParameter($name);
    }

    /**
     * Get all parameters from global config.
     *
     * @return array
     */
    public function getParameters()
    {
        return $this->container->getParameterBag()->all();
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
        return $this->container->get($name);
    }
}
