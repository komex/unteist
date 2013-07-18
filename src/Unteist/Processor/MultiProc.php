<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Processor;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Finder\Finder;
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
    protected $max_procs = 5;
    /**
     * @var Finder
     */
    protected $suites;
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
     * @param EventDispatcher $dispatcher
     */
    public function __construct(EventDispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
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
        return true;
    }
}