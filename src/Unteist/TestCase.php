<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Unteist\Exception\IncompleteTestException;
use Unteist\Exception\SkipTestException;
use Unteist\Exception\TestFailException;

/**
 * Class TestCase
 *
 * @package Unteist
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class TestCase implements EventSubscriberInterface
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
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * array('eventName' => 'methodName')
     *  * array('eventName' => array('methodName', $priority))
     *  * array('eventName' => array(array('methodName1', $priority), array('methodName2'))
     *
     * @return array The event names to listen to
     *
     * @api
     */
    public static function getSubscribedEvents()
    {
        return [];
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
