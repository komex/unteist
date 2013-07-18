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
     * @var array
     */
    protected $current_jobs = [];
    /**
     * @var array
     */
    protected $signal_queue = [];
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
        $result = true;
        if ($this->max_procs == 1) {
            foreach ($this->suites as $suite) {
                $result = $result && $this->executor($suite);
            }
        } else {
            pcntl_signal(SIGCHLD, [$this, 'childSignalHandler']);
            foreach ($this->suites as $suite) {
                $this->launchJob($suite);
                while (count($this->current_jobs) >= $this->max_procs) {
                    echo "Maximum children allowed, waiting...\n";
                    sleep(1);
                }
            }
            while (count($this->current_jobs)) {
                echo "Waiting for current jobs to finish... \n";
                sleep(1);
            }
        }

        return $result;
    }

    /**
     * Launch TestCase.
     *
     * @param SplFileInfo $suite
     *
     * @return bool
     */
    protected function executor(SplFileInfo $suite)
    {
        $class = TestCaseLoader::load($suite);
        if (!empty($this->class_filters)) {
            $reflection_class = new \ReflectionClass($class);
            foreach ($this->class_filters as $filter) {
                if (!$filter->filter($reflection_class)) {
                    return false;
                }
            }
        }
        $runner = new TestRunner($this->dispatcher);
        $runner->setFilters($this->methods_filters);
        $runner->setGlobalStorage($this->global_storage);
        $runner->precondition($class);

        return $runner->run();
    }

    /**
     * Launch TestCase in parallel processes.
     *
     * @param SplFileInfo $suite TestCase file
     *
     * @return bool
     */
    protected function launchJob(SplFileInfo $suite)
    {
        $pid = pcntl_fork();
        if ($pid == -1) {
            //Problem launching the job
            error_log('Could not launch new job, exiting');

            return false;
        } else {
            if ($pid) {
                // Parent process
                // Sometimes you can receive a signal to the childSignalHandler function before this code executes if
                // the child script executes quickly enough!
                //
                $this->current_jobs[$pid] = $suite;

                // In the event that a signal for this pid was caught before we get here, it will be in our signal_queue array
                // So let's go ahead and process it now as if we'd just received the signal
                if (isset($this->signal_queue[$pid])) {
                    echo "found $pid in the signal queue, processing it now \n";
                    $this->childSignalHandler(SIGCHLD, $pid, $this->signal_queue[$pid]);
                    unset($this->signal_queue[$pid]);
                }
            } else {
                exit($this->executor($suite));
            }
        }

        return true;
    }

    /**
     * Handler for process signals.
     *
     * @param int $signo
     * @param int $pid
     * @param int $status
     */
    public function childSignalHandler($signo, $pid = null, $status = null)
    {

        //If no pid is provided, that means we're getting the signal from the system.  Let's figure out
        //which child process ended
        if (!$pid) {
            $pid = pcntl_waitpid(-1, $status, WNOHANG);
        }

        //Make sure we get all of the exited children
        while ($pid > 0) {
            if ($pid && isset($this->current_jobs[$pid])) {
                $exit_code = pcntl_wexitstatus($status);
                if ($exit_code != 0) {
                    echo "$pid exited with status " . $exit_code . "\n";
                }
                unset($this->current_jobs[$pid]);
            } else {
                if ($pid) {
                    //Oh no, our job has finished before this parent process could even note that it had been launched!
                    //Let's make note of it and handle it when the parent process is ready for it
                    echo "..... Adding $pid to the signal queue ..... \n";
                    $this->signal_queue[$pid] = $status;
                }
            }
            $pid = pcntl_waitpid(-1, $status, WNOHANG);
        }
    }
}