<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

declare(ticks = 1);

namespace Unteist\Processor;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Unteist\Event\Connector;
use Unteist\Filter\ClassFilterInterface;
use Unteist\Filter\MethodsFilterInterface;
use Unteist\Strategy\Context;


/**
 * Class Processor
 *
 * @package Unteist\Processor
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class Processor
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
     * @var SplFileInfo[]
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
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var int
     */
    protected $strategy = Context::STRATEGY_IGNORE_FAILS;
    /**
     * @var int
     */
    protected $exit_code = 0;
    /**
     * @var Connector
     */
    protected $connector;

    /**
     * @param EventDispatcherInterface $dispatcher
     * @param LoggerInterface $logger
     */
    public function __construct(EventDispatcherInterface $dispatcher, LoggerInterface $logger)
    {
        $this->dispatcher = $dispatcher;
        $this->logger = $logger;
        $this->global_storage = new \ArrayObject();
        $this->connector = new Connector($this->dispatcher);
    }

    /**
     * Get current strategy
     *
     * @return int
     */
    public function getStrategy()
    {
        return $this->strategy;
    }

    /**
     * Set default strategy.
     *
     * @param int $strategy
     */
    public function setStrategy($strategy)
    {
        $this->strategy = intval($strategy, 10);
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
        $max_procs = intval($max_procs, 10);
        if ($max_procs < 1) {
            $max_procs = 1;
        }
        if ($max_procs > 10) {
            $max_procs = 10;
        }
        $this->max_procs = $max_procs;
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
     * @return int Exit code
     */
    public function run()
    {
        if ($this->max_procs == 1) {
            $this->logger->info('Run TestCases in single process.', ['pid' => getmypid()]);
            foreach ($this->suites as $suite) {
                if ($this->executor($suite)) {
                    $this->exit_code = 1;
                }
            }
        } else {
            $this->logger->info(
                'Run TestCases in forked processes.',
                ['pid' => getmypid(), 'procs' => $this->max_procs]
            );
            pcntl_signal(SIGCHLD, [$this, 'childSignalHandler']);
            foreach ($this->suites as $suite) {
                $this->launchJob($suite);
                while (count($this->current_jobs) >= $this->max_procs) {
                    $this->logger->debug(
                        'Maximum children allowed, waiting.',
                        ['jobs' => array_keys($this->current_jobs)]
                    );
                    $this->connector->read();
                }
            }
            while (count($this->current_jobs)) {
                $this->logger->debug(
                    'Waiting for current jobs to finish.',
                    ['jobs' => array_keys($this->current_jobs)]
                );
                $this->connector->read();
            }
            $this->connector->read();
        }
        $this->logger->info('All tests done.', ['pid' => getmypid(), 'exit_code' => $this->exit_code]);

        return $this->exit_code;
    }

    /**
     * Launch TestCase.
     *
     * @param SplFileInfo $case
     *
     * @return bool
     */
    protected function executor(SplFileInfo $case)
    {
        try {
            $this->logger->debug('Trying to load TestCase.', ['pid' => getmypid(), 'file' => $case->getPathname()]);
            $class = TestCaseLoader::load($case);
            $this->logger->debug('TestCase was found.', ['pid' => getmypid(), 'class' => get_class($class)]);
            if (!empty($this->class_filters)) {
                $reflection_class = new \ReflectionClass($class);
                foreach ($this->class_filters as $filter) {
                    if (!$filter->filter($reflection_class)) {
                        $this->logger->info(
                            'Class was filtered',
                            [
                                'pid' => getmypid(),
                                'filter' => $filter->getName(),
                            ]
                        );

                        return 1;
                    }
                }
            }
            $runner = new Runner($this->dispatcher, $this->logger);
            $runner->setStrategy($this->strategy);
            $runner->setFilters($this->methods_filters);
            $runner->setGlobalStorage($this->global_storage);
            $runner->precondition($class);

            return $runner->run();
        } catch (\RuntimeException $e) {
            $this->logger->notice('TestCase class does not found in file', ['pid' => getmypid(), 'exception' => $e]);

            return 1;
        }
    }

    /**
     * Launch TestCase in parallel processes.
     *
     * @param SplFileInfo $case TestCase file
     *
     * @return bool
     */
    protected function launchJob(SplFileInfo $case)
    {
        $this->connector->add();
        $pid = pcntl_fork();
        if ($pid == -1) {
            $this->logger->critical('Could not launch new job, exiting', ['file' => $case->getPathname()]);

            return false;
        } else {
            if ($pid) {
                $this->logger->debug('New fork.', ['pid' => getmypid(), 'child' => $pid]);
                $this->connector->attach($pid);
                // Parent process
                // Sometimes you can receive a signal to the childSignalHandler function before this code executes if
                // the child script executes quickly enough!
                $this->current_jobs[$pid] = $case;

                // In the event that a signal for this pid was caught before we get here, it will be in our signal_queue array
                // So let's go ahead and process it now as if we'd just received the signal
                if (isset($this->signal_queue[$pid])) {
                    $this->logger->info(
                        'Found new pid in the signal queue, processing it now.',
                        ['pid' => $pid, 'file' => $case->getPathname()]
                    );
                    $this->childSignalHandler(SIGCHLD, $pid, $this->signal_queue[$pid]);
                    unset($this->signal_queue[$pid]);
                }
            } else {
                $this->connector->activate();
                exit($this->executor($case));
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
    public function childSignalHandler($signo = null, $pid = null, $status = null)
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
                if ($exit_code == 0) {
                    $this->logger->debug('TestCase was successful finished.', ['pid' => $pid]);
                } else {
                    $this->logger->info(
                        'Process exited with status != 0.',
                        ['pid' => $pid, 'exit_code' => $exit_code]
                    );
                    $this->exit_code = $exit_code;
                }
                unset($this->current_jobs[$pid]);
            } else {
                if ($pid) {
                    //Oh no, our job has finished before this parent process could even note that it had been launched!
                    //Let's make note of it and handle it when the parent process is ready for it
                    $this->logger->debug('Adding process to signal queue.', ['pid' => $pid]);
                    $this->signal_queue[$pid] = $status;
                }
            }
            $pid = pcntl_waitpid(-1, $status, WNOHANG);
        }
    }
}