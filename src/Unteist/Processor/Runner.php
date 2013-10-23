<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Processor;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Unteist\Event\EventStorage;
use Unteist\Event\TestCaseEvent;
use Unteist\Exception\SkipTestException;
use Unteist\Filter\MethodsFilter;
use Unteist\Meta\TestMeta;
use Unteist\Processor\Controller\AbstractController;
use Unteist\Processor\Controller\RunTestsController;
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
    protected $testCase;
    /**
     * @var TestCaseEvent
     */
    protected $testCaseEvent;
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
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var ContainerBuilder
     */
    protected $container;
    /**
     * @var \ArrayIterator[]
     */
    private $dataSets = [];
    /**
     * @var \ReflectionClass
     */
    private $reflectionClass;
    /**
     * @var AbstractController
     */
    private $controller;

    /**
     * @param ContainerBuilder $container
     *
     * @return Runner
     */
    public function __construct(ContainerBuilder $container)
    {
        $this->container = $container;
        $this->logger = $this->container->get('logger');
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
     * @param AbstractController $controller
     */
    public function setController(AbstractController $controller)
    {
        $controller->setPrecondition($this->precondition);
        $controller->setRunner($this);
        $controller->setTestCaseEvent($this->testCaseEvent);
        $this->controller = $controller;
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
        $this->controller->beforeCase();
        foreach ($this->tests as $test) {
            if ($this->controller->test($test)) {
                $statusCode = 1;
            }
        }
        $this->controller->afterCase();

        return $statusCode;
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
    public function resolveDependencies(TestMeta $test)
    {
        $depends = $test->getDependencies();
        if (empty($depends)) {
            return;
        }
        $test->setStatus(TestMeta::TEST_MARKED);
        foreach ($depends as $depend) {
            $test = $this->getTestMethod($depend);
            switch ($test->getStatus()) {
                case TestMeta::TEST_NEW:
                    try {
                        $this->controller->test($test);
                    } catch (\Exception $e) {
                        throw new SkipTestException(
                            sprintf('Unresolved dependencies in %s::%s()', $this->reflectionClass->getName(), $depend),
                            0,
                            $e
                        );
                    }
                    break;
                case TestMeta::TEST_MARKED:
                    throw new \LogicException(
                        sprintf(
                            'Found infinitive loop in depends for test method "%s::%s()"',
                            $this->reflectionClass->getName(),
                            $depend
                        )
                    );
                case TestMeta::TEST_SKIPPED:
                    throw new SkipTestException(
                        sprintf('Test method "%s::%s()" was skipped', $this->reflectionClass->getName(), $depend)
                    );
                case TestMeta::TEST_FAILED:
                    throw new SkipTestException(
                        sprintf('Test method "%s::%s()" was failed', $this->reflectionClass->getName(), $depend)
                    );
                case TestMeta::TEST_INCOMPLETE:
                    throw new SkipTestException(
                        sprintf('Test method "%s::%s()" was uncompleted', $this->reflectionClass->getName(), $depend)
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
        $this->testCaseEvent = new TestCaseEvent($this->reflectionClass->getName());
        $this->testCaseEvent->setAnnotations(self::getAnnotations($this->reflectionClass->getDocComment()));
        $this->setController(new RunTestsController($this->container));
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
