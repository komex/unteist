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

/**
 * Class Processor
 *
 * @package Unteist\Processor
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class Processor
{
    /**
     * @var ClassFilterInterface[]
     */
    protected $classFilters = [];
    /**
     * @var MethodsFilterInterface[]
     */
    protected $methodsFilters = [];
    /**
     * @var ContainerBuilder
     */
    protected $container;
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var array
     */
    protected $globals = [];

    /**
     * Create general processor.
     *
     * @param ContainerBuilder $container
     */
    public function __construct(ContainerBuilder $container)
    {
        $this->container = $container;
        $this->logger = $this->container->get('logger');
    }

    /**
     * Set error handler for specified error types.
     *
     * @param array $errorTypes
     */
    public function setErrorHandler(array $errorTypes)
    {
        $type = 0;
        foreach ($errorTypes as $error) {
            $type |= constant($error);
        }
        set_error_handler([$this, 'errorHandler'], $type);
    }

    /**
     * Add new class filter or replace if its already exists.
     *
     * @param ClassFilterInterface $filter
     */
    public function addClassFilter(ClassFilterInterface $filter)
    {
        $this->classFilters[$filter->getName()] = $filter;
    }

    /**
     * Add new methods filter or replace if its already exists.
     *
     * @param MethodsFilterInterface $filter
     */
    public function addMethodsFilter(MethodsFilterInterface $filter)
    {
        $this->methodsFilters[$filter->getName()] = $filter;
    }

    /**
     * Handler for PHP errors.
     *
     * @param int $code Error code
     * @param string $message Error message
     *
     * @throws TestErrorException
     */
    public function errorHandler($code, $message)
    {
        throw new TestErrorException($message, $code);
    }

    /**
     * Run all TestCases.
     *
     * @param \ArrayObject $suites
     *
     * @return int Exit code
     */
    public function run(\ArrayObject $suites)
    {
        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = $this->container->get('dispatcher');
        $dispatcher->dispatch(EventStorage::EV_APP_STARTED);
        $this->logger->info('Run TestCases in single process.', ['pid' => getmypid()]);
        $this->backupGlobals();
        $exitCode = 0;
        foreach ($suites as $suite) {
            if ($this->executor($suite)) {
                $exitCode = 1;
            }
            gc_collect_cycles();
            $this->restoreGlobals();
        }
        $this->logger->info('All tests done.', ['pid' => getmypid(), 'exit_code' => $exitCode]);
        $dispatcher->dispatch(EventStorage::EV_APP_FINISHED);

        return $exitCode;
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
            if (!empty($this->classFilters)) {
                $reflectionClass = new \ReflectionClass($class);
                foreach ($this->classFilters as $filter) {
                    $filter->filter($reflectionClass);
                }
                unset($reflectionClass);
            }
            $class->setContainer($this->container);
            /** @var Runner $runner */
            $runner = $this->container->get('runner');
            $runner->setFilters($this->methodsFilters);

            return $runner->run($class);
        } catch (FilterException $e) {
            $this->logger->notice('File was filtered', ['pid' => getmypid(), 'filter' => $e]);
            /** @var EventDispatcherInterface $dispatcher */
            $dispatcher = $this->container->get('dispatcher');
            $dispatcher->dispatch(EventStorage::EV_CASE_FILTERED);

            return 1;
        }
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
