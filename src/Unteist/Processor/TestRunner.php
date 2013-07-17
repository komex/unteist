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
use Unteist\Exception\SkipException;
use Unteist\Filter\AbstractMethodsFilter;
use Unteist\Strategy\Context;
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
     * @var TestCase
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
     * @var Context
     */
    protected $context;
    /**
     * @var int
     */
    protected $strategy;
    /**
     * @var StatusSwitcher
     */
    protected $switcher;
    /**
     * @var \ArrayIterator[]
     */
    protected $data_sets = [];

    /**
     * @param EventDispatcher $dispatcher Global event dispatcher
     */
    public function __construct(EventDispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
        $this->tests = new \SplObjectStorage();
        $this->precondition = new EventDispatcher();
        $this->global_storage = new \ArrayObject();
        $this->context = new Context();
        $this->switcher = new StatusSwitcher($this->tests, $this->precondition, $this->dispatcher);
    }

    /**
     * Get current context.
     *
     * @return Context
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Set base strategy.
     *
     * @param int $strategy
     */
    public function setStrategy($strategy)
    {
        $this->strategy = $strategy;
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
        $class = new \ReflectionClass($this->test_case);
        $this->test_case_event = new TestCaseEvent($class->getName());
        unset($class);
        $this->dispatcher->dispatch(EventStorage::EV_BEFORE_CASE, $this->test_case_event);
        $this->precondition->dispatch(EventStorage::EV_BEFORE_CASE);
        $this->tests->rewind();
        while ($this->tests->valid()) {
            /** @var \ReflectionMethod $method */
            $method = $this->tests->current();
            /** @var array $data */
            $data = $this->tests->getInfo();

            $this->context->setStrategy($this->strategy);
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
     * @throws \Unteist\Exception\SkipException
     * @throws \RuntimeException
     */
    protected function runTest(\ReflectionMethod $method, array $data)
    {
        $test_event = new TestEvent($method->getName(), $this->test_case_event);
        $this->switcher->setTestEvent($test_event);
        $modifiers = $data['modifiers'];
        try {
            if (!empty($modifiers['depends'])) {
                $this->switcher->marked($method);
                $depends = preg_replace('{[^\w,]}i', '', $modifiers['depends']);
                $depends = explode(',', $depends);
                $test_event->setDepends($depends);
                $this->resolveDependencies($depends);
            }

            foreach ($this->getDataSet($modifiers['dataProvider']) as $data_set) {
                $test_event->setDataSet($data_set);
                $this->dispatcher->dispatch(EventStorage::EV_BEFORE_TEST, $test_event);
                $this->precondition->dispatch(EventStorage::EV_BEFORE_TEST, $test_event);
                $method->invokeArgs($this->test_case, $data_set);
                $this->switcher->done($method);
            }
        } catch (SkipException $skip) {
            $this->switcher->skipped($method);
            throw $skip;
        } catch (\Exception $e) {
            $this->switcher->failed($method);
            $this->context->fail($e);
        }
    }

    /**
     * Get data set from dataProvider method.
     *
     * @param string $method dataProvider method name
     *
     * @return \ArrayIterator
     * @throws \InvalidArgumentException
     */
    protected function getDataSet($method)
    {
        if (empty($method)) {
            return [[]];
        } else {
            if (empty($this->data_sets[$method])) {
                $data_set_method = new \ReflectionMethod($this->test_case, $method);
                $data_set = $data_set_method->invoke($this->test_case);
                if (is_array($data_set)) {
                    $this->data_sets[$method] = new \ArrayIterator($data_set);
                } elseif ($data_set instanceof \Iterator) {
                    $this->data_sets[$method] = $data_set;
                } else {
                    throw new \InvalidArgumentException(sprintf(
                        'DataProvider %s (%s) must return an array or Iterator object.',
                        $method,
                        $this->test_case_event->getName()
                    ));
                }
            } else {
                $this->data_sets[$method]->rewind();
            }

            return $this->data_sets[$method];
        }
    }

    /**
     * Check specified depends and run test if necessary.
     *
     * @param array $depends
     *
     * @throws \LogicException If found infinitive depends loop.
     * @throws \InvalidArgumentException If depends methods not found.
     * @throws \Unteist\Exception\SkipException If test method has skipped or failed method in depends.
     */
    protected function resolveDependencies(array $depends)
    {
        $class = new \ReflectionClass($this->test_case);
        foreach ($depends as $depend) {
            $method = $class->getMethod($depend);
            if ($this->tests->offsetExists($method)) {
                $data = $this->tests->offsetGet($method);
                switch ($data['status']) {
                    case self::TEST_NEW:
                        $this->context->setStrategy(Context::STRATEGY_SKIP_FAILS);
                        $this->runTest($method, $data);
                        $this->context->setStrategy($this->strategy);
                        break;
                    case self::TEST_MARKED:
                        throw new \LogicException(sprintf(
                            'Found infinitive loop in depends for test method %s (%s)',
                            $method->getName(),
                            $class->getName()
                        ));
                    case self::TEST_SKIPPED:
                        throw new SkipException(sprintf(
                            'Test method %s was skipped (%s).',
                            $method->getName(),
                            $class->getName()
                        ));
                    case self::TEST_FAILED:
                        throw new SkipException(sprintf(
                            'Test method %s was failed (%s).',
                            $method->getName(),
                            $class->getName()
                        ));
                }
            } else {
                throw new \InvalidArgumentException(sprintf(
                    'The depends method %s does not exists.',
                    $method->getName()
                ));
            }
        }
    }
}