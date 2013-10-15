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
     * @var resource
     */
    private $parentSocket;
    /**
     * @var resource
     */
    private $childSocket;
    /**
     * @var int
     */
    private $parentPID;
    /**
     * @var resource
     */
    private $queue;

    /**
     * @param EventDispatcherInterface $dispatcher
     * @param array $custom_events Custom events to proxy to parent process.
     */
    public function __construct(EventDispatcherInterface $dispatcher, array $custom_events = [])
    {
        $this->dispatcher = $dispatcher;
        $class = new \ReflectionClass('\\Unteist\\Event\\EventStorage');
        $this->events = array_unique(array_values($class->getConstants() + $custom_events));
        $this->parentPID = getmypid();
        $this->queue = msg_get_queue(ftok(__FILE__, 'U'));
    }

    /**
     * Add a new tunnel.
     * Calls from parent process only.
     *
     * @throws \RuntimeException If could not create a new pair socket
     */
    public function add()
    {
        list($this->parentSocket, $this->childSocket) = stream_socket_pair(
            STREAM_PF_UNIX,
            STREAM_SOCK_STREAM,
            STREAM_IPPROTO_IP
        );
        if ($this->parentSocket === null || $this->childSocket === null) {
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
        $this->sockets[$pid] = $this->childSocket;
        fclose($this->parentSocket);
        $this->parentSocket = null;
        $this->childSocket = null;
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
        fclose($this->childSocket);
    }

    /**
     * Listener for all events.
     *
     * @param Event $event
     *
     * @throws \RuntimeException If could not write event to socket
     * @internal This method calls automatically when $processes > 1.
     */
    public function onEvent(Event $event)
    {
        $serialized = serialize($event);
        $send_data = pack('N', strlen($serialized)) . $serialized;
        if (fwrite($this->parentSocket, $send_data) === false) {
            throw new \RuntimeException('Could not write event to socket.');
        }
        msg_send($this->queue, 1, getmypid(), false);
        posix_kill($this->parentPID, POLL_MSG);
    }

    /**
     * @throws \RuntimeException
     */
    public function read()
    {
        while (msg_receive($this->queue, 0, $pid, 128, $message, false, MSG_IPC_NOWAIT)) {
            $pid = intval($message);
            $socket = $this->sockets[$pid];
            if (feof($socket)) {
                continue;
            }
            $packed_len = stream_get_contents($socket, 4);
            $info = unpack('Nlen', $packed_len);
            $data = stream_get_contents($socket, $info['len']);
            /** @var Event $event */
            $event = unserialize($data);
            if (!empty($event) && $event instanceof Event) {
                $this->dispatcher->dispatch($event->getName(), $event);
            }
        }
    }
}
