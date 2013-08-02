<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Processor;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Unteist\Assert\Assert;
use Unteist\Event\EventStorage;
use Unteist\Event\TestCaseEvent;
use Unteist\Event\TestEvent;
use Unteist\Exception\AssertFailException;
use Unteist\Exception\SkipTestException;
use Unteist\Filter\MethodsFilterInterface;
use Unteist\Meta\TestMeta;
use Unteist\Strategy\Context;
use Unteist\TestCase;

/**
 * Class Runner
 *
 * @package Unteist\Processor
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class Runner
{
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
     * @var TestMeta[]
     */
    protected $tests;
    /**
     * @var MethodsFilterInterface[]
     */
    protected $filters = [];
    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;
    /**
     * @var EventDispatcher
     */
    protected $precondition;
    /**
     * @var Context
     */
    protected $context;
    /**
     * @var int
     */
    protected $strategy;
    /**
     * @var \ArrayIterator[]
     */
    protected $data_sets = [];
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var float
     */
    protected $started;
    /**
     * @var int
     */
    protected $asserts;

    /**
     * @param EventDispatcherInterface $dispatcher Global event dispatcher
     * @param LoggerInterface $logger
     *
     * @return Runner
     */
    public function __construct(EventDispatcherInterface $dispatcher, LoggerInterface $logger)
    {
        $this->dispatcher = $dispatcher;
        $this->logger = $logger;
        $this->precondition = new EventDispatcher();
        $this->context = new Context();
        $this->tests = new \ArrayObject();
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
                $this->tests[$method->getName()] = new TestMeta(
                    $this->name,
                    $method->getName(),
                    $modifiers,
                    $this->logger
                );
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
            $pattern = sprintf('{\*\s*@(%s)\b(?:\s+([\w\s]+))?[\r\n]*(?!\*)}', join('|', $keywords));
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
     * @return int Status code
     */
    public function run()
    {
        if (empty($this->tests)) {
            $this->logger->notice('Tests not found in TestCase', ['pid' => getmypid()]);

            return 1;
        }
        try {
            $this->test_case_event = new TestCaseEvent($this->name);
            $this->dispatcher->dispatch(EventStorage::EV_BEFORE_CASE, $this->test_case_event);
            $this->precondition->dispatch(EventStorage::EV_BEFORE_CASE);
            $return_code = 0;
            foreach ($this->tests as $meta) {
                $this->context->setStrategy($this->strategy);
                if ($this->runTest($meta)) {
                    $return_code = 1;
                }
            }
        } catch (\Exception $e) {
            $return_code = 1;
        }
        $this->precondition->dispatch(EventStorage::EV_AFTER_CASE);
        $this->dispatcher->dispatch(EventStorage::EV_AFTER_CASE, $this->test_case_event);

        return $return_code;
    }

    /**
     * Run test method.
     *
     * @param TestMeta $test
     *
     * @return int Status code
     * @throws \RuntimeException If something was wrong.
     * @throws SkipTestException If this test was skipped.
     * @throws AssertFailException If assert was fail.
     */
    protected function runTest(TestMeta $test)
    {
        $event = null;
        try {
            $depends = $test->getDependencies();
            if (!empty($depends)) {
                $test->setStatus(TestMeta::TEST_MARKED);
                $this->resolveDependencies($depends);
            }

            $status = $test->getStatus();
            if ($status == TestMeta::TEST_NEW || $status == TestMeta::TEST_MARKED) {
                $dataProvider = $this->getDataSet($test->getDataProvider());
                $method = new \ReflectionMethod($this->test_case, $test->getMethod());
                foreach ($dataProvider as $data_set) {

                    $event = new TestEvent($test->getMethod(), $this->test_case_event);
                    $event->setDataSet($data_set);
                    $event->setDepends($test->getDependencies());
                    $this->asserts = Assert::getAssertsCount();

                    $this->dispatcher->dispatch(EventStorage::EV_BEFORE_TEST, $event);
                    $this->started = microtime(true);
                    $this->precondition->dispatch(EventStorage::EV_BEFORE_TEST, $event);
                    $method->invokeArgs($this->test_case, $data_set);
                    $this->finish($test, $event, TestMeta::TEST_DONE);
                }
            }
        } catch (SkipTestException $e) {
            $this->logger->debug(
                'The test was skipped.',
                ['pid' => getmypid(), 'test' => $test->getMethod(), 'exception' => $e->getMessage()]
            );
            $event = new TestEvent($test->getMethod(), $this->test_case_event);
            $event->setException($e);
            $this->finish($test, $event, TestMeta::TEST_SKIPPED, false);
            $this->context->skipTest($e);
        } catch (AssertFailException $e) {
            $this->logger->debug(
                'Assert fail.',
                ['pid' => getmypid(), 'test' => $test->getMethod(), 'exception' => $e->getMessage()]
            );
            $event->setException($e);
            $this->finish($test, $event, TestMeta::TEST_FAILED);
            $this->context->assertFail($e);

            return 1;
        }

        //@todo: Обработка исключений от getDataSet()

        return 0;
    }

    /**
     * Check specified depends and run test if necessary.
     *
     * @param array $depends
     *
     * @throws \LogicException If found infinitive depends loop.
     * @throws \InvalidArgumentException If depends methods not found.
     * @throws SkipTestException If test method has skipped or failed method in depends.
     */
    protected function resolveDependencies(array $depends)
    {
        foreach ($depends as $depend) {
            if (!empty($this->tests[$depend])) {
                $test = $this->tests[$depend];
                switch ($test->getStatus()) {
                    case TestMeta::TEST_NEW:
                        $this->context->setStrategy(Context::STRATEGY_SKIP_FAILS);
                        $this->runTest($test);
                        $this->context->setStrategy($this->strategy);
                        break;
                    case TestMeta::TEST_MARKED:
                        throw new \LogicException(
                            sprintf('Found infinitive loop in depends for test method "%s:%s".', $this->name, $depend)
                        );
                    case TestMeta::TEST_SKIPPED:
                        throw new SkipTestException(
                            sprintf('Test method "%s:%s" was skipped.', $this->name, $depend)
                        );
                    case TestMeta::TEST_FAILED:
                        throw new SkipTestException(
                            sprintf('Test method "%s:%s" was failed.', $this->name, $depend)
                        );
                }
            } else {
                throw new \InvalidArgumentException(
                    sprintf('The depends method "%s:%s" does not exists or is not a test.', $this->name, $depend)
                );
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
        if (empty($method)) {
            return [[]];
        }
        if (empty($this->data_sets[$method])) {
            $data_set_method = new \ReflectionMethod($this->test_case, $method);
            $data_set = $data_set_method->invoke($this->test_case);
            //@todo: Обработка пустых data_set
            if (is_array($data_set)) {
                $this->data_sets[$method] = new \ArrayIterator($data_set);
            } elseif ($data_set instanceof \Iterator) {
                $this->data_sets[$method] = $data_set;
            } else {
                throw new \InvalidArgumentException(
                    sprintf('DataProvider "%s:%s" must return an array or Iterator object.', $this->name, $method)
                );
            }
        } else {
            $this->data_sets[$method]->rewind();
        }

        return $this->data_sets[$method];

    }

    /**
     * Do all dirty job after test is finish.
     *
     * @param TestMeta $test Meta description of test
     * @param TestEvent $event Test event
     * @param int $status Test status
     * @param bool $send_event Send After test event.
     */
    protected function finish(TestMeta $test, TestEvent $event, $status, $send_event = true)
    {
        $test->setStatus($status);
        $event->setStatus($status);
        $event->setDepends($test->getDependencies());
        $event->setTime(microtime(true) - $this->started);
        $event->setAsserts(Assert::getAssertsCount() - $this->asserts);
        switch ($status) {
            case TestMeta::TEST_DONE:
                $this->dispatcher->dispatch(EventStorage::EV_TEST_SUCCESS, $event);
                break;
            case TestMeta::TEST_SKIPPED:
                $this->dispatcher->dispatch(EventStorage::EV_TEST_SKIPPED, $event);
                break;
            case TestMeta::TEST_FAILED:
                $this->dispatcher->dispatch(EventStorage::EV_TEST_FAIL, $event);
                break;
        }
        if ($send_event) {
            $this->precondition->dispatch(EventStorage::EV_AFTER_TEST, $event);
            $this->dispatcher->dispatch(EventStorage::EV_AFTER_TEST, $event);
        }
    }
}
