<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Processor;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Unteist\Event\EventStorage;
use Unteist\Event\StorageEvent;
use Unteist\Exception\FilterException;
use Unteist\Exception\TestErrorException;
use Unteist\Filter\ClassFilterInterface;
use Unteist\Filter\MethodsFilterInterface;
use Unteist\Strategy\Context;
use Unteist\TestCase;

/**
 * Class Processor
 *
 * @package Unteist\Processor
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class Processor
{
    /**
     * @var \SplFileInfo[]
     */
    protected $suites = [];
    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;
    /**
     * @var ClassFilterInterface[]
     */
    protected $class_filters = [];
    /**
     * @var MethodsFilterInterface[]
     */
    protected $methods_filters = [];
    /**
     * @var \ArrayObject
     */
    protected $global_storage;
    /**
     * @var ContainerBuilder
     */
    protected $container;
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var int
     */
    protected $exit_code = 0;
    /**
     * @var array
     */
    protected $globals = [];
    /**
     * @var Context
     */
    protected $context;
    /**
     * @var int
     */
    protected $error_types;

    /**
     * Create general processor.
     *
     * @param EventDispatcherInterface $dispatcher
     * @param ContainerBuilder $container
     * @param LoggerInterface $logger
     * @param Context $context
     */
    public function __construct(
        EventDispatcherInterface $dispatcher,
        ContainerBuilder $container,
        LoggerInterface $logger,
        Context $context
    ) {
        $this->dispatcher = $dispatcher;
        $this->logger = $logger;
        $this->container = $container;
        $this->global_storage = new \ArrayObject();
        $this->context = $context;
    }

    /**
     * Set error types to handle.
     *
     * @param array $error_types
     */
    public function setErrorTypes(array $error_types)
    {
        $type = 0;
        foreach ($error_types as $error) {
            $type |= constant($error);
        }

        $this->error_types = $type;
    }

    /**
     * Set suite with tests.
     *
     * @param \ArrayObject|\SplFileInfo[] $suites
     */
    public function setSuite(\ArrayObject $suites)
    {
        $this->suites = $suites;
    }

    /**
     * Get a list of currently setup class filters.
     *
     * @return ClassFilterInterface[]
     */
    public function getClassFilters()
    {
        return $this->class_filters;
    }

    /**
     * Get a list of currently setup test methods filters.
     *
     * @return MethodsFilterInterface[]
     */
    public function getMethodsFilters()
    {
        return $this->methods_filters;
    }

    /**
     * Add new class filter or replace if its already exists.
     *
     * @param ClassFilterInterface $filter
     */
    public function addClassFilter(ClassFilterInterface $filter)
    {
        $this->class_filters[$filter->getName()] = $filter;
    }

    /**
     * Add new methods filter or replace if its already exists.
     *
     * @param MethodsFilterInterface $filter
     */
    public function addMethodsFilter(MethodsFilterInterface $filter)
    {
        $this->methods_filters[$filter->getName()] = $filter;
    }

    /**
     * Remove all class filters.
     */
    public function clearClassFilters()
    {
        $this->class_filters = [];
    }

    /**
     * Remove all methods filters.
     */
    public function clearMethodsFilters()
    {
        $this->methods_filters = [];
    }

    /**
     * Update global storage from event data.
     *
     * @param StorageEvent $event
     */
    public function updateStorage(StorageEvent $event)
    {
        $this->global_storage->unserialize($event->getData());
    }

    /**
     * Handler for PHP errors.
     *
     * @param int $errno
     * @param string $errstr
     *
     * @throws TestErrorException
     */
    public function errorHandler($errno, $errstr)
    {
        throw new TestErrorException($errstr, $errno);
    }

    /**
     * Run all TestCases.
     *
     * @return int Exit code
     */
    public function run()
    {
        set_error_handler([$this, 'errorHandler'], $this->error_types);
        $this->dispatcher->dispatch(EventStorage::EV_APP_STARTED);
        $this->logger->info('Run TestCases in single process.', ['pid' => getmypid()]);
        $this->backupGlobals();
        foreach ($this->suites as $suite) {
            if ($this->executor($suite)) {
                $this->exit_code = 1;
            }
            $this->restoreGlobals();
        }
        $this->logger->info('All tests done.', ['pid' => getmypid(), 'exit_code' => $this->exit_code]);
        $this->dispatcher->dispatch(EventStorage::EV_APP_FINISHED);

        return $this->exit_code;
    }

    /**
     * Launch TestCase.
     *
     * @param \SplFileInfo $case
     *
     * @return int
     */
    protected function executor(\SplFileInfo $case)
    {
        try {
            $this->logger->debug('Trying to load TestCase.', ['pid' => getmypid(), 'file' => $case->getRealPath()]);
            $class = TestCaseLoader::load($case);
            $this->logger->debug('TestCase was found.', ['pid' => getmypid(), 'class' => get_class($class)]);
            if (!empty($this->class_filters)) {
                $reflection_class = new \ReflectionClass($class);
                foreach ($this->class_filters as $filter) {
                    $filter->filter($reflection_class);
                }
            }

            return $this->runCase($class);
        } catch (FilterException $e) {
            $this->logger->notice('File was filtered', ['pid' => getmypid(), 'filter' => $e]);
            $this->dispatcher->dispatch(EventStorage::EV_CASE_FILTERED);

            return 1;
        }
    }

    /**
     * @param TestCase $case
     *
     * @return int
     */
    protected function runCase(TestCase $case)
    {
        $case->setGlobalStorage($this->global_storage);
        $case->setConfig($this->container);
        $case->setDispatcher($this->dispatcher);
        $runner = new Runner($this->dispatcher, $this->logger, $this->context);
        $runner->setFilters($this->methods_filters);
        $runner->precondition($case);

        return $runner->run();
    }

    /**
     * Backup all super global variables.
     */
    private function backupGlobals()
    {
        $this->globals = array_merge([], $GLOBALS);
    }

    /**
     * Restore all super global variables.
     */
    private function restoreGlobals()
    {
        $GLOBALS = $this->globals;
    }
}
