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
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Unteist\Assert\Assert;
use Unteist\Event\EventStorage;
use Unteist\Event\TestCaseEvent;
use Unteist\Event\TestEvent;
use Unteist\Exception\IncompleteTestException;
use Unteist\Exception\SkipTestException;
use Unteist\Exception\TestFailException;
use Unteist\Filter\MethodsFilter;
use Unteist\Meta\TestMeta;
use Unteist\Processor\Controller\BeforeTestController;
use Unteist\Processor\Controller\TestController;
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
     * @var TestCase
     */
    protected $test_case;
    /**
     * @var TestCaseEvent
     */
    protected $test_case_event;
    /**
     * @var TestMeta[]|\ArrayObject
     */
    protected $tests;
    /**
     * @var \Unteist\Filter\MethodsFilterInterface[]
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
     * @var \ArrayIterator[]
     */
    private $data_sets = [];
    /**
     * @var Context
     */
    private $context;
    /**
     * @var array
     */
    private $listeners = [];
    /**
     * @var \ReflectionClass
     */
    private $reflection_class;

    /**
     * @param EventDispatcherInterface $dispatcher Global event dispatcher
     * @param LoggerInterface $logger
     * @param Context $context
     *
     * @return Runner
     */
    public function __construct(EventDispatcherInterface $dispatcher, LoggerInterface $logger, Context $context)
    {
        $this->dispatcher = $dispatcher;
        $this->logger = $logger;
        $this->precondition = new EventDispatcher();
        $this->context = $context;
        $this->tests = new \ArrayObject();
    }

    /**
     * Parse block with annotations.
     *
     * @param string $doc Comments string
     * @param array $keywords Allowed keywords
     *
     * @return array
     */
    public static function parseDocBlock($doc, array $keywords)
    {
        if (empty($doc)) {
            $annotation = [];
        } else {
            $pattern = sprintf('{\*\s*@(%s)\b(?:\s+([\w\s\\\\]+))?[\r\n]*(?!\*)}', join('|', $keywords));
            preg_match_all($pattern, $doc, $matches, PREG_SET_ORDER);
            $annotation = [];
            foreach ($matches as $match) {
                $annotation[trim($match[1])] = trim($match[2]) ? : true;
            }
        }

        return $annotation;
    }

    /**
     * Get using TestCase.
     *
     * @return TestCase
     */
    public function getTestCase()
    {
        return $this->test_case;
    }

    /**
     * Set test method filters.
     *
     * @param \Unteist\Filter\MethodsFilterInterface[] $filters
     */
    public function setFilters(array $filters)
    {
        $this->filters = $filters;
    }

    /**
     * Run TestCase.
     *
     * @param TestCase $test_case
     *
     * @return int Status code
     */
    public function run(TestCase $test_case)
    {
        $this->precondition($test_case);
        if ($this->tests->count() == 0) {
            $this->logger->notice('Tests not found in TestCase', ['pid' => getmypid()]);
            $this->dispatcher->dispatch(EventStorage::EV_CASE_FILTERED);

            return 1;
        }
        $return_code = 0;
        $this->test_case_event = new TestCaseEvent($this->reflection_class->getName());
        $this->beforeCaseBehavior();
        foreach ($this->tests as $test) {
            try {
                if ($this->runTest($test)) {
                    $return_code = 1;
                }
            } catch (SkipTestException $e) {
                // Hack for reset execution time of skipped tests.
                $this->started = microtime(true);
                $event = new TestEvent($test->getMethod(), $this->test_case_event);
                $this->finish($test, $event, TestMeta::TEST_SKIPPED, $e, false);
                $return_code = 1;
            } catch (TestFailException $e) {
                $return_code = 1;
            } catch (IncompleteTestException $e) {
                $return_code = 1;
            }
        }
        $this->afterCaseBehavior();

        return $return_code;
    }

    /**
     * Do all dirty job after test is finish.
     *
     * @param TestMeta $test Meta description of test
     * @param TestEvent $event Test event
     * @param int $status Test status
     * @param \Exception $e
     * @param bool $send_event Send After test event.
     */
    public function finish(TestMeta $test, TestEvent $event, $status, \Exception $e = null, $send_event = true)
    {
        $test->setStatus($status);
        $event->setStatus($status);
        $event->setDepends($test->getDependencies());
        $event->setTime(floatval(microtime(true) - $this->started));
        $event->setAsserts(Assert::getAssertsCount() - $this->asserts);
        $this->asserts = Assert::getAssertsCount();
        if ($status === TestMeta::TEST_DONE) {
            $context = [];
        } else {
            $event->setException($e);
            $context = [
                'pid' => getmypid(),
                'test' => $test->getMethod(),
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ];
        }
        switch ($status) {
            case TestMeta::TEST_DONE:
                $this->dispatcher->dispatch(EventStorage::EV_TEST_SUCCESS, $event);
                break;
            case TestMeta::TEST_SKIPPED:
                $this->logger->debug('The test was skipped.', $context);
                $this->dispatcher->dispatch(EventStorage::EV_TEST_SKIPPED, $event);
                break;
            case TestMeta::TEST_FAILED:
                $this->logger->debug('Assert fail.', $context);
                $this->dispatcher->dispatch(EventStorage::EV_TEST_FAIL, $event);
                break;
            case TestMeta::TEST_INCOMPLETE:
                $this->logger->debug('Test incomplete.', $context);
                $this->dispatcher->dispatch(EventStorage::EV_TEST_INCOMPLETE, $event);
                break;
            default:
                $this->logger->critical('Unexpected exception.', $context);
                $this->dispatcher->dispatch(EventStorage::EV_TEST_ERROR, $event);
        }
        if ($send_event) {
            $this->precondition->dispatch(EventStorage::EV_AFTER_TEST, $event);
            $this->dispatcher->dispatch(EventStorage::EV_AFTER_TEST, $event);
        }
    }

    /**
     * Parse docBlock and gets Modifiers.
     *
     * @param \ReflectionMethod $method Parsed method
     *
     * @return array
     */
    protected function getModifiers(\ReflectionMethod $method)
    {
        return self::parseDocBlock(
            $method->getDocComment(),
            [
                'beforeTest',
                'afterTest',
                'beforeCase',
                'afterCase',
                'group',
                'depends',
                'dataProvider',
                'test',
                'expectedException',
                'expectedExceptionMessage',
                'expectedExceptionCode',
            ]
        );
    }

    /**
     * Check specified depends and run test if necessary.
     *
     * @param TestMeta $test
     *
     * @throws \LogicException If found infinitive depends loop.
     * @throws SkipTestException
     * @throws \InvalidArgumentException If depends methods not found.
     */
    protected function resolveDependencies(TestMeta $test)
    {
        $depends = $test->getDependencies();
        if (empty($depend)) {
            return;
        }
        $test->setStatus(TestMeta::TEST_MARKED);
        foreach ($depends as $depend) {
            if (!$this->tests->offsetExists($depend)) {
                if ($this->reflection_class->hasMethod($depend)) {
                    $method = $this->reflection_class->getMethod($depend);
                    $modifiers = $this->getModifiers($method);
                    if ($this->isTest($method, $modifiers)) {
                        $this->addTest($method, $modifiers);
                    }

                }
            }
            if (!$this->tests->offsetExists($depend)) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'The depends method "%s::%s()" does not exists or is not a test',
                        $this->reflection_class->getName(),
                        $depend
                    )
                );
            }
            $test = $this->tests[$depend];
            switch ($test->getStatus()) {
                case TestMeta::TEST_NEW:
                    try {
                        $this->runTest($test);
                    } catch (\Exception $e) {
                        throw new SkipTestException(
                            sprintf('Unresolved dependencies in %s::%s()', $this->reflection_class->getName(), $depend),
                            0,
                            $e
                        );
                    }
                    break;
                case TestMeta::TEST_MARKED:
                    throw new \LogicException(
                        sprintf(
                            'Found infinitive loop in depends for test method "%s::%s()"',
                            $this->reflection_class->getName(),
                            $depend
                        )
                    );
                case TestMeta::TEST_SKIPPED:
                    throw new SkipTestException(
                        sprintf('Test method "%s::%s()" was skipped', $this->reflection_class->getName(), $depend)
                    );
                case TestMeta::TEST_FAILED:
                    throw new SkipTestException(
                        sprintf('Test method "%s::%s()" was failed', $this->reflection_class->getName(), $depend)
                    );
            }
        }
    }

    /**
     * Get data set from dataProvider method.
     *
     * @param string $method dataProvider method name
     *
     * @return array[]
     * @throws \InvalidArgumentException
     */
    protected function getDataSet($method)
    {
        if (empty($method)) {
            return [[]];
        }
        if (empty($this->data_sets[$method])) {
            if (!method_exists($this->test_case, $method)) {
                throw new \InvalidArgumentException(
                    sprintf('DataProvider "%s::%s()" does not exists.', $this->reflection_class->getName(), $method)
                );
            }
            $data_set_method = new \ReflectionMethod($this->test_case, $method);
            $data_set = $data_set_method->invoke($this->test_case);
            //@todo: Обработка пустых data_set
            if (is_array($data_set)) {
                $this->data_sets[$method] = new \ArrayIterator($data_set);
            } elseif ($data_set instanceof \Iterator) {
                $this->data_sets[$method] = $data_set;
            } else {
                throw new \InvalidArgumentException(
                    sprintf(
                        'DataProvider "%s::%s()" must return an array or Iterator object.',
                        $this->reflection_class->getName(),
                        $method
                    )
                );
            }
        } else {
            $this->data_sets[$method]->rewind();
        }

        return $this->data_sets[$method];

    }

    /**
     * @param \ReflectionMethod $method
     *
     * @param array $modifiers
     *
     * @return bool
     */
    private function isTest(\ReflectionMethod $method, array $modifiers)
    {
        $method_filter = new MethodsFilter();
        $method_filter->setModifiers($modifiers);

        return $method_filter->condition($method);
    }

    /**
     * Check if method is filtered.
     *
     * @param \ReflectionMethod $method
     * @param array $modifiers
     *
     * @return bool
     */
    private function filtered(\ReflectionMethod $method, array $modifiers)
    {
        foreach ($this->filters as $filter) {
            $filter->setParams($modifiers);
            if (!$filter->condition($method)) {
                $this->logger->debug(
                    'Method is not a test.',
                    [
                        'pid' => getmypid(),
                        'method' => $method->getName(),
                        'filter' => $filter->getName()
                    ]
                );

                return true;
            }
        }

        return false;
    }

    /**
     * Setup TestCase.
     *
     * @param TestCase $test_case
     */
    private function precondition(TestCase $test_case)
    {
        $this->test_case = $test_case;
        $this->reflection_class = new \ReflectionClass($this->test_case);
        if ($test_case instanceof EventSubscriberInterface) {
            $this->listeners = $test_case->getSubscribedEvents();
            $this->dispatcher->addSubscriber($test_case);
            $this->precondition->addSubscriber($test_case);
        }
        foreach ($this->reflection_class->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($this->tests->offsetExists($method->getName())) {
                continue;
            }
            $modifiers = $this->getModifiers($method);
            if ($this->isTest($method, $modifiers)) {
                if ($this->filtered($method, $modifiers)) {
                    continue;
                }
                $this->addTest($method, $modifiers);
            } else {
                foreach (array_keys($modifiers) as $event) {
                    $this->registerEventListener($event, $method->getName());
                }
            }
        }
    }

    /**
     * @param \ReflectionMethod $method
     * @param array $modifiers
     */
    private function addTest(\ReflectionMethod $method, array $modifiers)
    {
        $this->tests[$method->getName()] = new TestMeta(
            $this->reflection_class->getName(),
            $method->getName(),
            $modifiers,
            $this->logger
        );
    }

    /**
     * Control behavior on after case.
     */
    private function afterCaseBehavior()
    {
        $this->precondition->dispatch(EventStorage::EV_AFTER_CASE);
        $this->dispatcher->dispatch(EventStorage::EV_AFTER_CASE, $this->test_case_event);
        foreach ($this->listeners as $event => $listener) {
            $this->dispatcher->removeListener($event, $listener);
        }
    }

    /**
     * Control behavior on before case.
     */
    private function beforeCaseBehavior()
    {
        try {
            $this->precondition->dispatch(EventStorage::EV_BEFORE_CASE);
            $this->dispatcher->dispatch(EventStorage::EV_BEFORE_CASE, $this->test_case_event);
        } catch (\Exception $e) {
            foreach ($this->tests as $test) {
                if ($test->getStatus() === TestMeta::TEST_NEW || $test->getStatus() === TestMeta::TEST_MARKED) {
                    $event = new TestEvent($test->getMethod(), $this->test_case_event);
                    $this->finish($test, $event, TestMeta::TEST_SKIPPED, $e);
                }
            }
        }
    }

    /**
     * Register method as an event listener.
     *
     * @param string $event Event name
     * @param string $listener The method name
     */
    private function registerEventListener($event, $listener)
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
     * Run test method.
     *
     * @param TestMeta $test
     *
     * @return int Status code
     * @throws \Exception If catch unexpected exception.
     */
    private function runTest(TestMeta $test)
    {
        $this->resolveDependencies($test);
        if ($test->getStatus() !== TestMeta::TEST_NEW && $test->getStatus() != TestMeta::TEST_MARKED) {
            return 0;
        }
        $status_code = 0;
        $dataProvider = $this->getDataSet($test->getDataProvider());
        foreach ($dataProvider as $dp_number => $data_set) {
            $event = new TestEvent($test->getMethod(), $this->test_case_event);
            if (count($dataProvider) > 1) {
                $event->setDataSet($dp_number + 1);
            }
            $event->setDepends($test->getDependencies());
            $controller = new BeforeTestController(
                $this->context,
                $this,
                $event,
                $this->dispatcher,
                $this->precondition
            );
            $controller->run();
            $this->started = microtime(true);
            $this->asserts = Assert::getAssertsCount();
            $controller = new TestController($this->context, $this, $event, $test, $data_set);
            $code = $controller->run();
            if ($code > 0) {
                $status_code = $code;
            }
        }

        return $status_code;
    }
}
