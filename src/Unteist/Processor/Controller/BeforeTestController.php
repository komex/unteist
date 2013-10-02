<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Processor\Controller;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Unteist\Event\EventStorage;
use Unteist\Event\TestEvent;
use Unteist\Processor\Runner;
use Unteist\Strategy\Context;

/**
 * Class BeforeTestController
 *
 * @package Unteist\Processor\Controller
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class BeforeTestController extends TestController
{
    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;
    /**
     * @var EventDispatcherInterface
     */
    protected $precondition;

    /**
     * @param Context $context
     * @param Runner $runner
     * @param TestEvent $event
     * @param EventDispatcherInterface $dispatcher
     * @param EventDispatcherInterface $precondition
     */
    public function __construct(
        Context $context,
        Runner $runner,
        TestEvent $event,
        EventDispatcherInterface $dispatcher,
        EventDispatcherInterface $precondition
    ) {
        $this->dispatcher = $dispatcher;
        $this->precondition = $precondition;
        $this->setContext($context);
        $this->setRunner($runner);
        $this->setEvent($event);
    }

    /**
     * Controller behavior.
     *
     * @return int Status code
     */
    protected function behavior()
    {
        $this->dispatcher->dispatch(EventStorage::EV_BEFORE_TEST, $this->event);
        $this->precondition->dispatch(EventStorage::EV_BEFORE_TEST, $this->event);

        return 0;
    }
}
