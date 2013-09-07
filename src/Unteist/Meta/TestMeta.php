<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Meta;

use Psr\Log\LoggerInterface;

/**
 * Class TestMeta
 *
 * @package Unteist\Meta
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class TestMeta
{
    /**
     * The test is just added to tests list.
     */
    const TEST_NEW = 0;
    /**
     * The test is done.
     */
    const TEST_DONE = 1;
    /**
     * The test was skipped.
     */
    const TEST_SKIPPED = 3;
    /**
     * The test was failed.
     */
    const TEST_FAILED = 4;
    /**
     * The test is marked as is already in stack list
     */
    const TEST_MARKED = 5;
    /**
     * Test marked as incomplete.
     */
    const TEST_INCOMPLETE = 6;
    /**
     * @var array
     */
    protected $dependencies = [];
    /**
     * @var string
     */
    protected $dataProvider;
    /**
     * @var int
     */
    protected $status = self::TEST_NEW;
    /**
     * @var string
     */
    protected $class;
    /**
     * @var string
     */
    protected $method;
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var string
     */
    protected $expected_exception;
    /**
     * @var string
     */
    protected $expected_exception_message;
    /**
     * @var int
     */
    protected $expected_exception_code;

    /**
     * @param string $class TestCase name
     * @param string $method Test name
     * @param array $modifiers
     * @param LoggerInterface $logger
     */
    public function __construct($class, $method, array $modifiers, LoggerInterface $logger)
    {
        $this->class = $class;
        $this->method = $method;
        $this->logger = $logger;
        $this->logger->debug(
            'Registering a new test method.',
            ['pid' => getmypid(), 'method' => $method, 'modifiers' => $modifiers]
        );
        // Depends
        if (!empty($modifiers['depends']) && is_string($modifiers['depends'])) {
            $depends = trim(preg_replace('{[^\w,]}i', '', $modifiers['depends']));
            if (!empty($depends)) {
                $depends = array_unique(explode(',', $depends));
                $position = array_search($method, $depends);
                if ($position !== false) {
                    array_splice($depends, $position, 1);
                }
                $this->logger->debug(
                    'The test has dependencies.',
                    ['pid' => getmypid(), 'test' => $method, 'depends' => $depends]
                );
                $this->dependencies = $depends;
            }
        }
        // DataProvider
        if (!empty($modifiers['dataProvider']) &&
            is_string($modifiers['dataProvider']) &&
            $modifiers['dataProvider'] != $method
        ) {
            $this->dataProvider = $modifiers['dataProvider'];
        }
        // Exceptions
        if (!empty($modifiers['expectedException']) && is_string($modifiers['expectedException'])) {
            $this->expected_exception = $modifiers['expectedException'];
            // Exception message
            if (!empty($modifiers['expectedExceptionMessage']) && is_string($modifiers['expectedExceptionMessage'])) {
                $this->expected_exception_message = $modifiers['expectedExceptionMessage'];
            }
            // Exception code
            if (!empty($modifiers['expectedExceptionCode']) && $modifiers['expectedExceptionCode'] !== true) {
                $this->expected_exception_code = intval($modifiers['expectedExceptionCode'], 10);
            }
        }
    }

    /**
     * Get class name of expected exception.
     *
     * @return string
     */
    public function getExpectedException()
    {
        return $this->expected_exception;
    }

    /**
     * Get code of expected exception.
     *
     * @return int
     */
    public function getExpectedExceptionCode()
    {
        return $this->expected_exception_code;
    }

    /**
     * Get message of expected exception.
     *
     * @return string
     */
    public function getExpectedExceptionMessage()
    {
        return $this->expected_exception_message;
    }

    /**
     * Get TestCase name.
     *
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * Get dapaProvider name.
     *
     * @return string
     */
    public function getDataProvider()
    {
        return $this->dataProvider;
    }

    /**
     * Get dependencies tests' names.
     *
     * @return array
     */
    public function getDependencies()
    {
        return $this->dependencies;
    }

    /**
     * Get test name.
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Get test status.
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set current test status.
     *
     * @param int $status
     */
    public function setStatus($status)
    {
        $this->status = intval($status, 10);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->class . '::' . $this->method;
    }
}
