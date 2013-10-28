<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Processor\Controller;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Unteist\Processor\Runner;

/**
 * Interface ControllerChildConfigurableInterface
 *
 * @package Unteist\Processor\Controller
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
interface ControllerChildConfigurableInterface extends ControllerChildInterface
{
    /**
     * @param Runner $runner
     */
    public function setRunner(Runner $runner);

    /**
     * @param EventDispatcherInterface $precondition
     */
    public function setPrecondition(EventDispatcherInterface $precondition);
}
