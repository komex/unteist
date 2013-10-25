<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Processor\Controller;

/**
 * Interface ControllerParentInterface
 *
 * @package Unteist\Processor\Controller
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
interface ControllerParentInterface extends ControllerInterface
{
    /**
     * Use specified controller.
     *
     * @param string $id
     *
     * @throws \InvalidArgumentException
     */
    public function switchTo($id);

    /**
     * @param ControllerChildInterface $controller
     * @param string $id
     */
    public function add(ControllerChildInterface $controller, $id);
}
