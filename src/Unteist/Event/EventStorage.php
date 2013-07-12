<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Event;

/**
 * Class EventStorage
 *
 * @package Unteist\Event
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
final class EventStorage
{
    const EV_BEFORE_CASE = 'case.before';
    const EV_BEFORE_TEST = 'test.before';
    const EV_AFTER_TEST = 'test.after';
    const EV_AFTER_CASE = 'case.after';
}