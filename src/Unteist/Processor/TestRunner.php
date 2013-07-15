<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Processor;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Unteist\Event\EventStorage;
use Unteist\Event\TestCaseEvent;
use Unteist\Event\TestEvent;
use Unteist\Filter\AbstractMethodsFilter;

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
     * @var \ReflectionClass
     */
    protected $test_case;
    /**
     * @var TestCaseEvent
     */
    protected $test_case_event;
    /**
     * @var \SplObjectStorage
     */
    protected $tests;
    /**
     * @var AbstractMethodsFilter[]
     */
    protected $filters = [];
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
     * @param \ReflectionClass $test_case
     */
    public function precondition(\ReflectionClass $test_case)
    {
        $this->test_case = $test_case;
        foreach ($this->test_case->getMethods() as $method) {
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

    /**
     * Run TestCase.
     *
     * @return bool
     */
    public function run()
    {
        if ($this->tests->count() == 0) {
            return false;
        }
        $this->test_case_event = new TestCaseEvent($this->test_case->getName());
        $this->dispatcher->dispatch(EventStorage::EV_BEFORE_CASE, $this->test_case_event);
        $this->precondition->dispatch(EventStorage::EV_BEFORE_CASE);
        $this->tests->rewind();
        while ($this->tests->valid()) {
            /** @var \ReflectionMethod $method */
            $method = $this->tests->current();
            /** @var array $data */
            $data = $this->tests->getInfo();

            $this->runTest($method, $data);
            $this->tests->next();
        }
        $this->precondition->dispatch(EventStorage::EV_AFTER_CASE);
        $this->dispatcher->dispatch(EventStorage::EV_AFTER_CASE, $this->test_case_event);

        return true;
    }

    /**
     * Run test method.
     *
     * @param \ReflectionMethod $method
     * @param array $data
     *
     * @return bool Is this test success?
     */
    protected function runTest(\ReflectionMethod $method, array $data)
    {
        $test_event = new TestEvent($method->getName(), $this->test_case_event);
        $this->dispatcher->dispatch(EventStorage::EV_BEFORE_TEST, $test_event);
        $this->precondition->dispatch(EventStorage::EV_BEFORE_TEST);

        $modifiers = $data['modifiers'];
        $continue = true;
        if (!empty($modifiers['depends'])) {
            $data['status'] = self::TEST_MARKED;
            $this->tests->offsetSet($method, $data);
            $depends = preg_replace('{[^\w,]}i', '', $modifiers['depends']);
            $depends = explode(',', $depends);
            $test_event->setDepends($depends);
            if (!$this->resolveDependencies($depends)) {
                $data['status'] = self::TEST_SKIPPED;
                $this->tests->offsetSet($method, $data);

                $continue = false;
            }
        }

        if ($continue) {

        }

        $this->precondition->dispatch(EventStorage::EV_AFTER_TEST);
        $this->dispatcher->dispatch(EventStorage::EV_AFTER_TEST, $test_event);

        return $continue;
    }

    /**
     * Check specified depends and run test if necessary.
     *
     * @param array $depends
     *
     * @return bool Is depends conditions OK?
     * @throws \LogicException If found infinitive depends loop.
     * @throws \InvalidArgumentException If depends methods not found.
     */
    protected function resolveDependencies(array $depends)
    {
        foreach ($depends as $depend) {
            $method = $this->test_case->getMethod($depend);
            if ($this->tests->offsetExists($method)) {
                $data = $this->tests->offsetGet($method);
                switch ($data['status']) {
                    case self::TEST_DONE:
                        break;
                    case self::TEST_NEW:
                        if (!$this->runTest($method, $data)) {
                            return false;
                        }
                        break;
                    case self::TEST_MARKED:
                        throw new \LogicException(sprintf(
                            'Found infinitive loop in depends (%s)',
                            $this->test_case->getName()
                        ));
                    default:
                        return false;
                }
            } else {
                throw new \InvalidArgumentException(sprintf(
                    'The depends method %s does not exists.',
                    $method->getName()
                ));
            }
        }

        return true;
    }
}