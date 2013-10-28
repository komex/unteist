<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Processor;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Unteist\Event\EventStorage;
use Unteist\Event\MethodEvent;
use Unteist\Event\TestCaseEvent;
use Unteist\Filter\MethodsFilter;
use Unteist\Filter\MethodsFilterInterface;
use Unteist\Meta\TestMeta;
use Unteist\Processor\Controller\ControllerParentInterface;
use Unteist\TestCase;

/**
 * Class Runner
 *
 * @package Unteist\Processor
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class Runner extends ContainerAware implements LoggerAwareInterface
{
    /**
     * @var TestCase
     */
    protected $testCase;
    /**
     * @var TestMeta[]|\ArrayObject
     */
    protected $tests;
    /**
     * @var \Unteist\Filter\MethodsFilterInterface[]
     */
    protected $filters = [];
    /**
     * @var EventDispatcher
     */
    protected $precondition;
    /**
     * @var \ArrayIterator[]
     */
    private $dataSets = [];
    /**
     * @var \ReflectionClass
     */
    private $reflectionClass;
    /**
     * @var ControllerParentInterface
     */
    private $controller;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @return Runner
     */
    public function __construct()
    {
        $this->precondition = new EventDispatcher();
        $this->tests = new \ArrayObject();
    }

    /**
     * Get raw annotations for reflected object.
     *
     * @param string $comments
     *
     * @return array
     */
    public static function getAnnotations($comments)
    {
        if (empty($comments) or !is_string($comments)) {
            return [];
        } else {
            preg_match_all('{\*\s*@([a-z]+)\b(?:[\t ]+([^\n]+))?[\r\n]*(?!\*)}i', $comments, $matches, PREG_SET_ORDER);
            $result = [];
            foreach ($matches as $match) {
                $result[$match[1]] = count($match) === 2 ? null : $match[2];
            }

            return $result;
        }
    }

    /**
     * Sets a logger instance on the object
     *
     * @param LoggerInterface $logger
     *
     * @return null
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Get TestCase precondition event dispatcher.
     *
     * @return EventDispatcher
     */
    public function getPrecondition()
    {
        return $this->precondition;
    }

    /**
     * Get using TestCase.
     *
     * @return TestCase
     */
    public function getTestCase()
    {
        return $this->testCase;
    }

    /**
     * Add new methods filter or replace if its already exists.
     *
     * @param MethodsFilterInterface $filter
     */
    public function addMethodsFilter(MethodsFilterInterface $filter)
    {
        $this->filters[$filter->getName()] = $filter;
    }

    /**
     * @param ControllerParentInterface $controller
     */
    public function setController(ControllerParentInterface $controller)
    {
        $this->controller = $controller;
        $this->controller->setRunner($this);
        $this->controller->switchTo('controller.run');
    }

    /**
     * Run TestCase.
     *
     * @param TestCase $testCase
     *
     * @return int Status code
     */
    public function run(TestCase $testCase)
    {
        $this->precondition($testCase);
        if ($this->tests->count() == 0) {
            $this->logger->notice('Tests not found in TestCase', ['pid' => getmypid()]);
            /** @var EventDispatcherInterface $dispatcher */
            $dispatcher = $this->container->get('dispatcher');
            $dispatcher->dispatch(EventStorage::EV_CASE_FILTERED);

            return 1;
        }
        $statusCode = 0;
        $testCaseEvent = new TestCaseEvent($this->reflectionClass->getName());
        $testCaseEvent->setAnnotations(self::getAnnotations($this->reflectionClass->getDocComment()));
        $this->controller->beforeCase($testCaseEvent);
        foreach ($this->tests as $test) {
            if ($test->getStatus() !== TestMeta::TEST_NEW && $test->getStatus() !== TestMeta::TEST_MARKED) {
                continue;
            }
            if ($this->testMethod($test)) {
                $statusCode = 1;
            }
        }
        $this->controller->afterCase($testCaseEvent);

        return $statusCode;
    }

    /**
     * Method lifecycle.
     *
     * @param TestMeta $test
     *
     * @return int Status code
     */
    public function testMethod(TestMeta $test)
    {
        $statusCode = 0;
        $this->controller->resolveDependencies($test);
        $dataProvider = $this->controller->getDataSet($test);
        foreach ($dataProvider as $index => $dataSet) {
            /** @var MethodEvent $event */
            $event = $this->container->get('event.method');
            $event->configByTestMeta($test);
            if (count($dataProvider) > 1) {
                $event->setDataSet($index + 1);
            }
            $this->controller->beforeTest($event);
            if ($this->controller->test($test, $event, $dataSet)) {
                $statusCode = 1;
            }
            $this->controller->afterTest($event);
        }

        return $statusCode;
    }

    /**
     * Check specified depends and run test if necessary.
     *
     * @param TestMeta $test
     *
     * @throws \LogicException If found infinitive depends loop
     */
    public function resolveDependencies(TestMeta $test)
    {
        $depends = $test->getDependencies();
        if (empty($depends)) {
            return;
        }
        $test->setStatus(TestMeta::TEST_MARKED);
        foreach ($depends as $depend) {
            $test = $this->getTestMethod($depend);
            if ($test->getStatus() === TestMeta::TEST_NEW) {
                if ($this->testMethod($test)) {
                    $this->controller->switchTo(ControllerParentInterface::CONTROLLER_SKIP_ONCE);
                }
            } elseif ($test->getStatus() === TestMeta::TEST_MARKED) {
                throw new \LogicException(
                    sprintf(
                        'Found infinitive loop in depends for test method "%s::%s()"',
                        $this->reflectionClass->getName(),
                        $depend
                    )
                );
            } else {
                $this->controller->switchTo(ControllerParentInterface::CONTROLLER_SKIP_ONCE);
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
    public function getDataSet($method)
    {
        if (empty($method)) {
            return [[]];
        }
        if (empty($this->dataSets[$method])) {
            if (!$this->reflectionClass->hasMethod($method)) {
                throw new \InvalidArgumentException(
                    sprintf('DataProvider "%s::%s()" does not exists.', $this->reflectionClass->getName(), $method)
                );
            }
            $dataSet = $this->reflectionClass->getMethod($method)->invoke($this->testCase);
            //@todo: Обработка пустых dataSet
            if (is_array($dataSet)) {
                $this->dataSets[$method] = new \ArrayIterator($dataSet);
            } elseif ($dataSet instanceof \Iterator) {
                $this->dataSets[$method] = $dataSet;
            } else {
                throw new \InvalidArgumentException(
                    sprintf(
                        'DataProvider "%s::%s()" must return an array or Iterator object.',
                        $this->reflectionClass->getName(),
                        $method
                    )
                );
            }
        }
        $this->dataSets[$method]->rewind();

        return $this->dataSets[$method];

    }

    /**
     * Get test method by name.
     * If test does not exits in list, method tries to add it.
     *
     * @param string $method Method name
     *
     * @return TestMeta
     * @throws \InvalidArgumentException If test method does not exists
     */
    private function getTestMethod($method)
    {
        if ($this->tests->offsetExists($method)) {
            return $this->tests->offsetGet($method);
        }
        if ($this->reflectionClass->hasMethod($method)) {
            $reflectionMethod = $this->reflectionClass->getMethod($method);
            $annotations = self::getAnnotations($reflectionMethod->getDocComment());
            if ($this->isTest($reflectionMethod, $annotations)) {
                return $this->addTest($reflectionMethod, $annotations);
            }
        }
        throw new \InvalidArgumentException(
            sprintf(
                'The depends method "%s::%s()" does not exists or is not a test',
                $this->reflectionClass->getName(),
                $method
            )
        );
    }

    /**
     * @param \ReflectionMethod $method
     *
     * @param array $annotations
     *
     * @return bool
     */
    private function isTest(\ReflectionMethod $method, array $annotations)
    {
        $methodFilter = new MethodsFilter();
        $methodFilter->setAnnotations($annotations);

        return $methodFilter->condition($method);
    }

    /**
     * Check if method is filtered.
     *
     * @param \ReflectionMethod $method
     * @param array $annotations
     *
     * @return bool
     */
    private function filtered(\ReflectionMethod $method, array $annotations)
    {
        foreach ($this->filters as $filter) {
            $filter->setAnnotations($annotations);
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
     * @param TestCase $testCase
     */
    private function precondition(TestCase $testCase)
    {
        $this->testCase = $testCase;
        $this->reflectionClass = new \ReflectionClass($this->testCase);
        if ($testCase instanceof EventSubscriberInterface) {
            $this->precondition->addSubscriber($testCase);
        }
        foreach ($this->reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($this->tests->offsetExists($method->getName())) {
                continue;
            }
            $annotations = self::getAnnotations($method->getDocComment());
            if ($this->isTest($method, $annotations)) {
                if ($this->filtered($method, $annotations)) {
                    continue;
                }
                $this->addTest($method, $annotations);
            } else {
                foreach (array_keys($annotations) as $event) {
                    $this->registerEventListener($event, $method->getName());
                }
            }
        }
    }

    /**
     * @param \ReflectionMethod $method
     * @param array $annotations
     *
     * @return TestMeta
     */
    private function addTest(\ReflectionMethod $method, array $annotations)
    {
        $className = $this->reflectionClass->getName();
        $methodName = $method->getName();
        $this->tests[$methodName] = new TestMeta($className, $methodName, $annotations, $this->logger);

        return $this->tests[$methodName];
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
            $this->precondition->addListener($name, array($this->testCase, $listener));
        }
    }
}
