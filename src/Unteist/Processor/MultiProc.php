<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

declare(ticks = 1);

namespace Unteist\Processor;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Unteist\Filter\ClassFilterInterface;
use Unteist\Filter\MethodsFilterInterface;


/**
 * Class MultiProc
 *
 * @package Unteist\Processor
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class MultiProc
{
    /**
     * @var int
     */
    protected $max_procs = 1;
    /**
     * @var Finder|SplFileInfo[]
     */
    protected $suites = [];
    /**
     * @var EventDispatcher
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
     * @param EventDispatcher $dispatcher
     */
    public function __construct(EventDispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
        $this->global_storage = new \ArrayObject();
    }

    /**
     * @param Finder $suites
     */
    public function setSuites(Finder $suites)
    {
        $this->suites = $suites;
    }

    /**
     * Get number of maximum processes.
     *
     * @return int
     */
    public function getMaxProcs()
    {
        return $this->max_procs;
    }

    /**
     * Set number of maximum processes.
     *
     * @param int $max_procs
     */
    public function setMaxProcs($max_procs)
    {
        if ($max_procs < 1) {
            $max_procs = 1;
        }
        if ($max_procs > 10) {
            $max_procs = 10;
        }
        $this->max_procs = intval($max_procs, 10);
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
     * Run all TestCases.
     *
     * @return bool
     */
    public function run()
    {
        if ($this->max_procs == 1) {
            foreach ($this->suites as $suite) {
                // @todo: Load class and run executor
                $class = TestCaseLoader::load($suite);
                if (!empty($this->class_filters)) {
                    $reflection_class = new \ReflectionClass($class);
                    foreach ($this->class_filters as $filter) {
                        if (!$filter->filter($reflection_class)) {
                            continue 2;
                        }
                    }
                }
                $runner = new TestRunner($this->dispatcher);
                $runner->setFilters($this->methods_filters);
                $runner->setGlobalStorage($this->global_storage);
                $runner->precondition($class);
                $runner->run();
            }

        } else {
            // @todo: Run all suites in different processes
            pcntl_signal(SIGCHLD, [$this, 'childSignalHandler']);
        }

        return true;
    }
}