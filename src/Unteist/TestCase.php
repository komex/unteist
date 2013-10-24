<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist;

use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Unteist\Exception\IncompleteTestException;
use Unteist\Exception\SkipTestException;
use Unteist\Exception\TestFailException;

/**
 * Class TestCase
 *
 * @package Unteist
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class TestCase extends ContainerAware
{
    /**
     * @var \ArrayObject
     */
    protected $localStorage;
    /**
     * @var ContainerBuilder
     */
    private $container;

    /**
     * Create a new TestCase.
     */
    public function __construct()
    {
        $this->localStorage = new \ArrayObject();
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
