<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Processor\Controller;

/**
 * Interface ControllerChildInterface
 *
 * @package Unteist\Processor\Controller
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
interface ControllerChildInterface extends ControllerInterface
{
    /**
     * Set parent controller for behavior controller.
     *
     * @param ControllerParentInterface $parent
     */
    public function setParent(ControllerParentInterface $parent);
}
