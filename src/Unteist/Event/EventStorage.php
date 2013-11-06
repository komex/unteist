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
    /**
     * Calls on application started.
     */
    const EV_APP_STARTED = 'application.started';
    /**
     * The case was filtered.
     */
    const EV_CASE_FILTERED = 'case.filtered';
    /**
     * Calls before all tests in TestCase.
     */
    const EV_BEFORE_CASE = 'case.before';
    /**
     * Calls before each test in TestCase.
     */
    const EV_BEFORE_TEST = 'test.before';
    /**
     * Calls after each success method.
     */
    const EV_METHOD_DONE = 'method.done';
    /**
     * Calls after each skipped method.
     */
    const EV_METHOD_SKIPPED = 'method.skipped';
    /**
     * Calls after each failed method.
     */
    const EV_METHOD_FAILED = 'method.failed';
    /**
     * Calls after each incomplete method.
     */
    const EV_METHOD_INCOMPLETE = 'method.incomplete';
    /**
     * Calls after each test in TestCase.
     */
    const EV_AFTER_TEST = 'test.after';
    /**
     * Calls after all tests in TestCase.
     */
    const EV_AFTER_CASE = 'case.after';
    /**
     * Calls on application finished.
     */
    const EV_APP_FINISHED = 'application.finished';
    /**
     * Global storage was changed - processor shall update it.
     */
    const EV_STORAGE_GLOBAL_UPDATE = 'storage.global.update';
}
