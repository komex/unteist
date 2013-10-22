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
    protected $expectedException;
    /**
     * @var string
     */
    protected $expectedExceptionMessage;
    /**
     * @var int
     */
    protected $expectedExceptionCode;

    /**
     * @param string $class TestCase name
     * @param string $method Test name
     * @param array $annotations
     * @param LoggerInterface $logger
     */
    public function __construct($class, $method, array $annotations, LoggerInterface $logger)
    {
        $this->class = $class;
        $this->method = $method;
        $this->logger = $logger;
        $this->logger->debug(
            'Registering a new test method.',
            ['pid' => getmypid(), 'method' => $method, 'annotations' => $annotations]
        );
        // Depends
        $this->setDependencies($annotations);
        // DataProvider
        $this->setDataProvider($annotations);
        // Exceptions
        $this->setExpectedException($annotations);
    }

    /**
     * Get class name of expected exception.
     *
     * @return string
     */
    public function getExpectedException()
    {
        return $this->expectedException;
    }

    /**
     * Get code of expected exception.
     *
     * @return int
     */
    public function getExpectedExceptionCode()
    {
        return $this->expectedExceptionCode;
    }

    /**
     * Get message of expected exception.
     *
     * @return string
     */
    public function getExpectedExceptionMessage()
    {
        return $this->expectedExceptionMessage;
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

    /**
     * Set meta information about expected exception.
     *
     * @param array $annotations
     */
    private function setExpectedException(array $annotations)
    {
        if (!empty($annotations['expectedException'])) {
            $this->expectedException = $annotations['expectedException'];
            // Exception message
            if (!empty($annotations['expectedExceptionMessage'])) {
                $this->expectedExceptionMessage = $annotations['expectedExceptionMessage'];
            }
            // Exception code
            if (!empty($annotations['expectedExceptionCode'])) {
                $this->expectedExceptionCode = intval($annotations['expectedExceptionCode'], 10);
            }
        }
    }

    /**
     * Set meta information about data provider for test.
     *
     * @param array $annotations
     */
    private function setDataProvider(array $annotations)
    {
        if (!empty($annotations['dataProvider']) and $annotations['dataProvider'] != $this->method) {
            $this->dataProvider = $annotations['dataProvider'];
        }
    }

    /**
     * Set meta information about test dependencies.
     *
     * @param array $annotations
     */
    private function setDependencies(array $annotations)
    {
        if (!empty($annotations['depends'])) {
            $depends = trim(preg_replace('{[^\w,]}i', '', $annotations['depends']));
            if (!empty($depends)) {
                $depends = array_unique(explode(',', $depends));
                /** @var int $position */
                $position = array_search($this->method, $depends);
                if ($position !== false) {
                    array_splice($depends, $position, 1);
                }
                $this->logger->debug(
                    'The test has dependencies.',
                    ['pid' => getmypid(), 'test' => $this->method, 'depends' => $depends]
                );
                $this->dependencies = $depends;
            }
        }
    }
}
