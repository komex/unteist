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
}
