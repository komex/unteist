<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Processor;

use Unteist\Event\Connector;
use Unteist\Event\EventStorage;
use Unteist\Event\StorageEvent;

/**
 * Class MultiProcessor
 *
 * @package Unteist\Processor
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class MultiProcessor extends Processor
{
    /**
     * @var int
     */
    protected $processes = 1;
    /**
     * @var \SplFileInfo[]
     */
    protected $current_jobs = [];
    /**
     * @var array
     */
    protected $signal_queue = [];
    /**
     * @var Connector
     */
    protected $connector;

    /**
     * Set connector for multi processor working.
     *
     * @param Connector $connector
     */
    public function setConnector(Connector $connector)
    {
        $this->connector = $connector;
    }

    /**
     * Set number of maximum processes.
     *
     * @param int $processes Number of using processes.
     */
    public function setProcesses($processes)
    {
        $processes = intval($processes, 10);
        if ($processes < 1) {
            $processes = 1;
        }
        if ($processes > 10) {
            $processes = 10;
        }
        $this->processes = $processes;
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
     * Run all TestCases.
     *
     * @return int Exit code
     */
    public function run()
    {
        set_error_handler([$this, 'errorHandler'], $this->error_types);
        $this->dispatcher->dispatch(EventStorage::EV_APP_STARTED);
        $this->logger->info(
            'Run TestCases in forked processes.',
            ['pid' => getmypid(), 'procs' => $this->processes]
        );
        declare(ticks = 1);
        pcntl_signal(SIGCHLD, [$this, 'childSignalHandler']);
        $this->dispatcher->addListener(EventStorage::EV_STORAGE_GLOBAL_UPDATE, [$this, 'updateStorage']);
        foreach ($this->suites as $suite) {
            $this->launchJob($suite);
            while (count($this->current_jobs) >= $this->processes) {
                $this->logger->debug(
                    'Maximum children allowed, waiting.',
                    ['jobs' => array_keys($this->current_jobs)]
                );
                $this->connector->read();
            }
        }
        while (count($this->current_jobs) > 0) {
            $this->logger->debug(
                'Waiting for current jobs to finish.',
                ['jobs' => array_keys($this->current_jobs)]
            );
            $this->connector->read();
        }
        $this->connector->read();
        $this->logger->info('All tests done.', ['pid' => getmypid(), 'exit_code' => $this->exit_code]);
        $this->dispatcher->dispatch(EventStorage::EV_APP_FINISHED);

        return $this->exit_code;
    }

    /**
     * Launch TestCase in parallel processes.
     *
     * @param \SplFileInfo $case TestCase file
     *
     * @return bool
     */
    private function launchJob(\SplFileInfo $case)
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

                // In the event that a signal for this pid was caught before we get here,
                // it will be in our signal_queue array.
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
                $hash = sha1($this->global_storage->serialize());
                $status_code = $this->executor($case);
                $data = $this->global_storage->serialize();
                if (sha1($data) !== $hash) {
                    $this->dispatcher->dispatch(EventStorage::EV_STORAGE_GLOBAL_UPDATE, new StorageEvent($data));
                }
                exit($status_code);
            }
        }

        return true;
    }
}
