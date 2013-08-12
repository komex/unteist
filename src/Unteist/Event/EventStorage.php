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
     * Calls after each success test.
     */
    const EV_TEST_SUCCESS = 'test.success';
    /**
     * Calls after each skipped test.
     */
    const EV_TEST_SKIPPED = 'test.skipped';
    /**
     * Calls after each fail test.
     */
    const EV_TEST_FAIL = 'test.fail';
    /**
     * Calls after each test with error.
     */
    const EV_TEST_ERROR = 'test.error';
    /**
     * Calls after each incomplete test.
     */
    const EV_TEST_INCOMPLETE = 'test.incomplete';
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
