<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Meta;

use Psr\Log\LoggerInterface;
use Unteist\Processor\Runner;


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
    const TEST_SKIPPED = 2;
    /**
     * The test was failed.
     */
    const TEST_FAILED = 4;
    /**
     * The test is marked as is already in stack list
     */
    const TEST_MARKED = 8;
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
        if (!empty($modifiers['depends'])) {
            $depends = preg_replace('{[^\w,]}i', '', $modifiers['depends']);
            $depends = array_unique(explode(',', $depends));
            $position = array_search($method, $depends);
            if ($position !== false) {
                array_splice($depends, $position, 1);
            }
            $this->logger->debug(
                'The test has depends',
                ['pid' => getmypid(), 'test' => $method, 'depends' => $depends]
            );
            $this->dependencies = $depends;
        }
        if (!empty($modifiers['dataProvider']) && $modifiers['dataProvider'] != $method) {
            $this->dataProvider = $modifiers['dataProvider'];
        }
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