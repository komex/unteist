<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Event;


use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class Connector
 *
 * @package Unteist\Event
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class Connector
{
    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;
    /**
     * @var array
     */
    protected $events;
    /**
     * @var resource[]
     */
    protected $sockets = [];
    /**
     * @var resource[]
     */
    protected $current_sockets = [];

    /**
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
        $class = new \ReflectionClass('\\Unteist\\Event\\EventStorage');
        $this->events = array_values($class->getConstants());
    }

    /**
     * Add a new tunnel.
     * Calls from parent process only.
     *
     * @throws \RuntimeException If could not create a new pair socket
     */
    public function add()
    {
        $this->current_sockets = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        if ($this->current_sockets === false) {
            throw new \RuntimeException(
                sprintf('Could not create a new pair socket: %s', socket_strerror(socket_last_error()))
            );
        }
    }

    /**
     * Attach last created socket to child pid.
     *
     * @param int $pid Child pid
     */
    public function attach($pid)
    {
        $this->sockets[$pid] = $this->current_sockets[1];
        fclose($this->current_sockets[0]);
        $this->current_sockets = [];
    }

    /**
     * Activate cross processes tunnel.
     * NB: Should be called only in child process!
     * Removes all current registered listeners.
     */
    public function activate()
    {
        foreach ($this->dispatcher->getListeners() as $event => $listeners) {
            foreach ($listeners as $listener) {
                $this->dispatcher->removeListener($event, $listener);
            }
        }
        foreach ($this->events as $event) {
            $this->dispatcher->addListener($event, [$this, 'onEvent']);
        }
        foreach ($this->sockets as $socket) {
            fclose($socket);
        }
        fclose($this->current_sockets[1]);
    }

    /**
     * Listener for all events.
     *
     * @param Event $event
     *
     * @throws \RuntimeException If could not write event to socket
     * @internal This method calls automatically when $max_procs > 1.
     */
    public function onEvent(Event $event)
    {
        $serialized = serialize($event) . PHP_EOL;
        if (fwrite($this->current_sockets[0], $serialized) === false) {
            throw new \RuntimeException('Could not write event to socket.');
        }
    }

    /**
     * @throws \RuntimeException
     */
    public function read()
    {
        if (empty($this->sockets)) {
            return;
        }
        $read = $this->sockets;
        $num = stream_select($read, $write = null, $exception = null, null, 500000);
        if ($num === false) {
            throw new \RuntimeException(
                sprintf('Cannot read from sockets, reason: %s', socket_strerror(socket_last_error()))
            );
        }
        if ($num > 0) {
            foreach ($read as $socket) {
                $data = fgets($socket);
                if (!empty($data)) {
                    /** @var Event $event */
                    $event = unserialize($data);
                    if (!empty($event)) {
                        $this->dispatcher->dispatch($event->getName(), $event);
                    }
                }
            }
        }
    }
}
