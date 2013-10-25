<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Processor\Controller;

use Unteist\Event\MethodEvent;

/**
 * Class SkipOnce
 *
 * @package Unteist\Processor\Controller
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class SkipOnce extends Skip
{
    /**
     * Actions after each test.
     *
     * @param MethodEvent $event
     */
    public function afterTest(MethodEvent $event)
    {
        parent::afterTest($event);
        $this->parent->switchTo('controller.run');
    }
}
