<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Processor;


use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Unteist\Event\EventStorage;
use Unteist\Event\TestCaseEvent;
use Unteist\Event\TestEvent;
use Unteist\Exception\AssertException;
use Unteist\Exception\SkipException;
use Unteist\Filter\MethodsFilterInterface;
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
     * @var string
     */
    protected $name;
    /**
     * @var TestCase
     */
    protected $test_case;
    /**
     * @var TestCaseEvent
     */
    protected $test_case_event;
    /**
     * @var \ArrayObject
     */
    protected $tests;
    /**
     * @var MethodsFilterInterface[]
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
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param EventDispatcher $dispatcher Global event dispatcher
     * @param LoggerInterface $logger
     */
    public function __construct(EventDispatcher $dispatcher, LoggerInterface $logger)
    {
        $this->dispatcher = $dispatcher;
        $this->logger = $logger;
        $this->precondition = new EventDispatcher();
        $this->context = new Context();
        $this->tests = new \ArrayObject();
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
        $this->name = $class->getName();
        foreach ($class->getMethods() as $method) {
            $is_test_method = true;
            $modifiers = $this->parseDocBlock($method);
            foreach ($this->filters as $filter) {
                if (!$filter->condition($method, $modifiers)) {
                    $this->logger->debug(
                        'Method is NOT a test.',
                        [
                            'pid' => getmypid(),
                            'method' => $method->getName(),
                            'modifiers' => $modifiers,
                            'filter' => $filter->getName()
                        ]
                    );
                    $is_test_method = false;
                    break;
                }
            }
            if ($is_test_method) {
                $this->logger->debug(
                    'Registering a new test method.',
                    ['pid' => getmypid(), 'method' => $method->getName(), 'modifiers' => $modifiers]
                );
                $this->tests[$method->getName()] = [
                    'status' => self::TEST_NEW,
                    'modifiers' => $modifiers,
                ];
            } else {
                foreach (array_keys($modifiers) as $event) {
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
        if (empty($doc)) {
            $modifiers = [];
        } else {
            $keywords = [
                'beforeTest',
                'afterTest',
                'beforeCase',
                'afterCase',
                'group',
                'depends',
                'dataProvider',
                'test'
            ];
            $pattern = sprintf('{\*\s*@(%s)(?:\s+([\w\s]+))?[\r\n]*(?!\*)}', join('|', $keywords));
            preg_match_all($pattern, $doc, $matches, PREG_SET_ORDER);
            $modifiers = [];
            foreach ($matches as $match) {
                $modifiers[trim($match[1])] = trim($match[2]) ? : true;
            }
        }

        return $modifiers;
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
            $this->logger->debug(
                'Register a new event listener',
                ['pid' => getmypid(), 'event' => $event, 'method' => $listener]
            );
            $this->precondition->addListener($name, array($this->test_case, $listener));
        }
    }

    /**
     * Set test method filters.
     *
     * @param MethodsFilterInterface[] $filters
     */
    public function setFilters(array $filters)
    {
        $this->filters = $filters;
    }

    /**
     * Run TestCase.
     *
     * @return bool
     */
    public function run()
    {
        if (empty($this->tests)) {
            $this->logger->notice('Tests not found in TestCase', ['pid' => getmypid()]);

            return false;
        }
        $this->test_case_event = new TestCaseEvent($this->name);
        $this->dispatcher->dispatch(EventStorage::EV_BEFORE_CASE, $this->test_case_event);
        $this->precondition->dispatch(EventStorage::EV_BEFORE_CASE);
        foreach ($this->tests as $method => $data) {
            $this->context->setStrategy($this->strategy);
            $this->runTest($method, $data);
        }
        $this->precondition->dispatch(EventStorage::EV_AFTER_CASE);
        $this->dispatcher->dispatch(EventStorage::EV_AFTER_CASE, $this->test_case_event);

        return true;
    }

    /**
     * Run test method.
     *
     * @param string $test Method name
     * @param array $data
     *
     * @throws \Unteist\Exception\SkipException
     * @throws \RuntimeException
     */
    protected function runTest($test, array $data)
    {
        $test_event = new TestEvent($test, $this->test_case_event);
        $this->switcher->setTestEvent($test_event);
        $modifiers = $data['modifiers'];
        try {
            if (!empty($modifiers['depends'])) {
                $this->switcher->marked($test);
                $depends = preg_replace('{[^\w,]}i', '', $modifiers['depends']);
                $depends = array_unique(explode(',', $depends));
                $this->logger->debug(
                    'The test has depends',
                    ['pid' => getmypid(), 'test' => $test, 'depends' => $depends]
                );
                $test_event->setDepends($depends);
                $this->resolveDependencies($depends);
            }

            $status = $this->tests[$test]['status'];
            if ($status == self::TEST_NEW || $status == self::TEST_MARKED) {
                if (empty($modifiers['dataProvider'])) {
                    $dataProvider = [[]];
                } else {
                    $dataProvider = $this->getDataSet($modifiers['dataProvider']);
                }
                $method = new \ReflectionMethod($this->test_case, $test);
                foreach ($dataProvider as $data_set) {
                    $test_event->setDataSet($data_set);
                    $this->switcher->eventBeforeTest();
                    $method->invokeArgs($this->test_case, $data_set);
                    $this->switcher->done($test);
                }
            }
        } catch (SkipException $skip) {
            $this->switcher->skipped($test);
            throw $skip;
        } catch (AssertException $e) {
            $this->logger->debug(
                'Assert fail.',
                ['pid' => getmypid(), 'test' => $test, 'exception' => $e->getMessage()]
            );
            $this->switcher->failed($test);
            $this->context->fail($e);
        } catch (\Exception $e) {
            $this->switcher->failed($test);
            $this->context->fail($e);
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
        foreach ($depends as $depend) {
            if (!empty($this->tests[$depend])) {
                $data = $this->tests[$depend];
                switch ($data['status']) {
                    case self::TEST_NEW:
                        $this->context->setStrategy(Context::STRATEGY_SKIP_FAILS);
                        $this->runTest($depend, $data);
                        $this->context->setStrategy($this->strategy);
                        break;
                    case self::TEST_MARKED:
                        throw new \LogicException(sprintf(
                            'Found infinitive loop in depends for test method "%s:%s".',
                            $this->name,
                            $depend
                        ));
                    case self::TEST_SKIPPED:
                        throw new SkipException(sprintf(
                            'Test method "%s:%s" was skipped.',
                            $this->name,
                            $depend
                        ));
                    case self::TEST_FAILED:
                        throw new SkipException(sprintf(
                            'Test method "%s:%s" was failed.',
                            $this->name,
                            $depend
                        ));
                }
            } else {
                throw new \InvalidArgumentException(sprintf(
                    'The depends method "%s:%s" does not exists or is not a test.',
                    $this->name,
                    $depend
                ));
            }
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
        if (empty($this->data_sets[$method])) {
            $data_set_method = new \ReflectionMethod($this->test_case, $method);
            $data_set = $data_set_method->invoke($this->test_case);
            if (is_array($data_set)) {
                $this->data_sets[$method] = new \ArrayIterator($data_set);
            } elseif ($data_set instanceof \Iterator) {
                $this->data_sets[$method] = $data_set;
            } else {
                throw new \InvalidArgumentException(sprintf(
                    'DataProvider "%s:%s" must return an array or Iterator object.',
                    $this->name,
                    $method
                ));
            }
        } else {
            $this->data_sets[$method]->rewind();
        }

        return $this->data_sets[$method];

    }
}