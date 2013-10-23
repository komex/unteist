<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class TestCaseEvent
 *
 * @package Unteist\Event
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class TestCaseEvent extends Event
{
    /**
     * @var string
     */
    protected $class;
    /**
     * @var array
     */
    protected $annotations;

    /**
     * @param string $class Test case class
     */
    public function __construct($class)
    {
        $this->class = $class;
    }

    /**
     * Get test's namespace.
     *
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * Get class annotations.
     *
     * @return array
     */
    public function getAnnotations()
    {
        return $this->annotations;
    }

    /**
     * Set class annotations.
     *
     * @param array $annotations
     */
    public function setAnnotations(array $annotations)
    {
        $this->annotations = $annotations;
    }
}
