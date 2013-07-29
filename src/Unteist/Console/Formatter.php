<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Console;

use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\ProgressHelper;
use Symfony\Component\Console\Output\OutputInterface;
use Unteist\Event\TestEvent;


/**
 * Class Formatter
 *
 * @package Unteist\Console
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class Formatter
{
    /**
     * @var OutputInterface
     */
    protected $output;
    /**
     * @var ProgressHelper
     */
    protected $progress;

    /**
     * @param OutputInterface $output
     * @param ProgressHelper $progress
     */
    public function __construct(OutputInterface $output, ProgressHelper $progress)
    {
        $this->output = $output;
        $this->progress = $progress;
    }

    /**
     * @param string $level
     * @param string $file
     */
    public function loggerInformation($level, $file)
    {
        $this->output->writeln(
            sprintf('The <info>%s</info> logs will be written to <comment>%s</comment>.', $level, $file)
        );
    }

    /**
     * @param int $count
     */
    public function start($count)
    {
        $this->output->writeln(
            sprintf('Found <comment>%d</comment> %s.', $count, $count === 1 ? 'file' : 'files')
        );
        $this->progress->start($this->output, $count);
        $this->progress->display();
    }

    /**
     *
     */
    public function advance()
    {
        $this->progress->advance();
    }

    /**
     * Display result information.
     *
     * @param float $time
     * @param int $success
     * @param \SplDoublyLinkedList $skipped
     * @param \SplDoublyLinkedList $fail
     * @param int $asserts
     */
    public function finish($time, $success, \SplDoublyLinkedList $skipped, \SplDoublyLinkedList $fail, $asserts)
    {
        $this->progress->finish();
        $this->output->writeln(sprintf('Time: <comment>%F</comment> seconds.', $time));
        $this->output->writeln('');
        if ($fail->count() > 0) {
            $this->fail($success, $skipped, $fail, $asserts);
        } elseif ($success > 0) {
            $this->success($success, $skipped, $asserts);
        }
    }

    /**
     * Output when one or more tests fail.
     *
     * @param int $success
     * @param \SplDoublyLinkedList|TestEvent[] $skipped
     * @param \SplDoublyLinkedList|TestEvent[] $fail
     * @param int $asserts
     */
    protected function fail($success, \SplDoublyLinkedList $skipped, \SplDoublyLinkedList $fail, $asserts)
    {
        if ($skipped->count() > 0) {
            $this->testOutput('Skipped tests:', 'comment', $skipped);
        }
        $this->testOutput('Failed tests:', 'error', $fail);

        $this->output->writeln('');
        $this->output->writeln(
            sprintf(
                '<error>FAILURES! Tests: %d, Skipped: %d, Assertions: %d, Failures: %d</error>',
                $success,
                $skipped->count(),
                $asserts,
                $fail->count()
            )
        );
    }

    /**
     * Print failed or skipped tests with stack trace.
     *
     * @param string $title Group title
     * @param string $tag Tag name for color output
     * @param \SplDoublyLinkedList|TestEvent[] $tests
     */
    protected function testOutput($title, $tag, \SplDoublyLinkedList $tests)
    {
        $this->output->writeln($title);
        foreach ($tests as $i => $test) {
            $exception = $test->getException();
            if (empty($exception)) {
                $message = '';
                $trace = '';
            } else {
                $message = $exception->getMessage();
                $trace = $exception->getTraceAsString();
            }
            $this->output->writeln(
                sprintf('<%3$s>%d.</%3$s> %s', ($i + 1), $message, $tag)
            );
            $this->output->writeln($trace);
        }
    }

    /**
     * Output when all test success.
     *
     * @param int $success
     * @param \SplDoublyLinkedList|TestEvent[] $skipped
     * @param int $asserts
     */
    protected function success($success, \SplDoublyLinkedList $skipped, $asserts)
    {
        $style = new OutputFormatterStyle('black', 'green');
        $this->output->getFormatter()->setStyle('success', $style);
        $this->output->writeln(
            sprintf(
                '<success>OK (Tests: %d, Skipped: %d, Asserts: %d)</success>',
                $success,
                $skipped->count(),
                $asserts
            )
        );
    }
}