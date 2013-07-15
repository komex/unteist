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
     * Calls before all tests in TestCase
     */
    const EV_BEFORE_CASE = 'case.before';
    /**
     * Calls before each test in TestCase
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
     * Calls after each test in TestCase
     */
    const EV_AFTER_TEST = 'test.after';
    /**
     * Calls after all tests in TestCase
     */
    const EV_AFTER_CASE = 'case.after';
}