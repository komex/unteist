<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Processor\Controller;

use Unteist\Processor\Runner;

/**
 * Interface ControllerParentInterface
 *
 * @package Unteist\Processor\Controller
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
interface ControllerParentInterface extends ControllerInterface
{
    /**
     * Run test normally.
     */
    const CONTROLLER_RUN = 'controller.run';
    /**
     * Skip all tests in case.
     */
    const CONTROLLER_SKIP = 'controller.skip';
    /**
     * Skip only one test.
     */
    const CONTROLLER_SKIP_ONCE = 'controller.skip.once';

    /**
     * Use specified controller.
     *
     * @param string $id
     *
     * @throws \InvalidArgumentException
     */
    public function switchTo($id);

    /**
     * @param Runner $runner
     */
    public function setRunner(Runner $runner);

    /**
     * @return Runner
     */
    public function getRunner();
}
