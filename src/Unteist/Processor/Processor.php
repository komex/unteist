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
use Unteist\Exception\FilterException;
use Unteist\Exception\TestErrorException;
use Unteist\Filter\ClassFilterInterface;
use Unteist\Filter\MethodsFilterInterface;
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
     * @var int
     */
    protected $error_types;

    /**
     * Create general processor.
     *
     * @param ContainerBuilder $container
     * @param \ArrayObject $suites
     */
    public function __construct(
        ContainerBuilder $container,
        \ArrayObject $suites
    ) {
        $this->container = $container;
        $this->dispatcher = $this->container->get('dispatcher');
        $this->logger = $this->container->get('logger');
        $this->suites = $suites;
        $this->global_storage = new \ArrayObject();
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
        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = $this->container->get('dispatcher');
        $dispatcher->dispatch(EventStorage::EV_APP_STARTED);
        $this->logger->info('Run TestCases in single process.', ['pid' => getmypid()]);
        $this->backupGlobals();
        foreach ($this->suites as $suite) {
            if ($this->executor($suite)) {
                $this->exit_code = 1;
            }
            $this->restoreGlobals();
        }
        $this->logger->info('All tests done.', ['pid' => getmypid(), 'exit_code' => $this->exit_code]);
        $dispatcher->dispatch(EventStorage::EV_APP_FINISHED);

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
            /** @var EventDispatcherInterface $dispatcher */
            $dispatcher = $this->container->get('dispatcher');
            $dispatcher->dispatch(EventStorage::EV_CASE_FILTERED);

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
        $runner = new Runner($this->container);
        $runner->setFilters($this->methods_filters);

        return $runner->run($case);
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
