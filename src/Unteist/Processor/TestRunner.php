<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Processor;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Unteist\Event\EventStorage;
use Unteist\Filter\AbstractMethodsFilter;
use Unteist\TestCase;

/**
 * Class TestRunner
 *
 * @package Unteist\Processor
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class TestRunner
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
     * The test is marked as is already in stack list
     */
    const TEST_MARKED = 2;
    /**
     * @var TestCase
     */
    protected $test_case;
    /**
     * @var \SplObjectStorage
     */
    protected $tests;
    /**
     * @var AbstractMethodsFilter[]
     */
    protected $filters = [];
    /**
     * @var \SplDoublyLinkedList
     */
    protected $stack;
    /**
     * @var EventDispatcher
     */
    protected $dispatcher;
    /**
     * @var EventDispatcher
     */
    protected $precondition;
    /**
     * @var int
     */
    protected $asserts_count = 0;
    /**
     * @var \ArrayObject
     */
    protected $global_storage;

    /**
     * @param EventDispatcher $dispatcher Global event dispatcher
     */
    public function __construct(EventDispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
        $this->tests = new \SplObjectStorage();
        $this->stack = new \SplDoublyLinkedList();
        $this->precondition = new EventDispatcher();
        $this->global_storage = new \ArrayObject();
    }

    /**
     * Get global storage.
     *
     * @param \ArrayObject $global_storage
     */
    public function setGlobalStorage(\ArrayObject $global_storage)
    {
        $this->global_storage = $global_storage;
    }

    /**
     * Setup TestCase.
     *
     * @param TestCase $test_case
     */
    public function precondition(TestCase $test_case)
    {
        $this->test_case = $test_case;
        $class = new \ReflectionClass($this->test_case);
        foreach ($class->getMethods() as $method) {
            $is_test_method = true;
            $modifiers = $this->parseDocBlock($method);
            foreach ($this->filters as $filter) {
                if (!$filter->condition($method)) {
                    $is_test_method = false;
                    break;
                }
            }
            if ($is_test_method || in_array('test', strtolower($modifiers['name']))) {
                $this->tests->attach(
                    $method,
                    [
                        'status' => self::TEST_NEW,
                        'modifiers' => $modifiers,
                    ]
                );
            } else {
                foreach ($modifiers as $event) {
                    $this->registerEventListener($event, $method->getName());
                }
            }
        }
    }

    /**
     * Parse docBlock and gets Modifiers.
     *
     * @param \ReflectionMethod $method Parsed method
     *
     * @return array
     */
    protected function parseDocBlock(\ReflectionMethod $method)
    {
        $doc = $method->getDocComment();
        if (!empty($doc)) {
            $keywords = ['beforeTest', 'afterTest', 'beforeCase', 'afterCase', 'group', 'depends', 'dataProvider'];
            $pattern = sprintf('{\*\s*@(?P<name>%s)(?:\s+(?P<value>[\w,\s]+))?\n(?!\*)}', join('|', $keywords));
            preg_match_all($pattern, $doc, $matches);
            unset($matches[0]);
            unset($matches[1]);
            unset($matches[2]);
        } else {
            $matches = [];
        }

        return $matches;
    }

    /**
     * Register method as an event listener.
     *
     * @param string $event Event name
     * @param string $listener The method name
     */
    protected function registerEventListener($event, $listener)
    {
        switch ($event) {
            case 'beforeTest':
                $name = EventStorage::EV_BEFORE_TEST;
                break;
            case 'afterTest':
                $name = EventStorage::EV_AFTER_TEST;
                break;
            case 'beforeCase':
                $name = EventStorage::EV_BEFORE_CASE;
                break;
            case 'afterCase':
                $name = EventStorage::EV_AFTER_CASE;
                break;
            default:
                $name = null;
        }
        if (!empty($name)) {
            $this->precondition->addListener($name, array($this->test_case, $listener));
        }
    }

    /**
     * Set test method filters.
     *
     * @param AbstractMethodsFilter[] $filters
     */
    public function setFilters(array $filters)
    {
        $this->filters = $filters;
    }

    /**
     * Increase the count of used asserts in TestCase.
     */
    public function incAssertCount()
    {
        $this->asserts_count++;
    }

    /**
     * Get the current number of used asserts in TestCase.
     *
     * @return int
     */
    public function getAssertCount()
    {
        return $this->asserts_count;
    }

    public function run()
    {

    }

    protected function runTest(\ReflectionMethod $test)
    {

    }
}