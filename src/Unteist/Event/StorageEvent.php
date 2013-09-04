<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class StorageEvent
 *
 * @package Unteist\Event
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class StorageEvent extends Event
{
    /**
     * @var string
     */
    protected $data;

    /**
     * @param string $data Serialized ArrayObject data
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Get serialized ArrayObject data
     *
     * @return string Serialized ArrayObject data
     */
    public function getData()
    {
        return $this->data;
    }
}
