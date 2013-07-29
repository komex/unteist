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
     * @param $time
     * @param $tests_success
     * @param \SplDoublyLinkedList|TestEvent[] $tests_skipped
     * @param \SplDoublyLinkedList|TestEvent[] $tests_fail
     * @param $asserts
     */
    public function finish(
        $time,
        $tests_success,
        \SplDoublyLinkedList $tests_skipped,
        \SplDoublyLinkedList $tests_fail,
        $asserts
    ) {
        $this->progress->finish();
        $this->output->writeln(sprintf('Time: <comment>%F</comment> seconds.', $time));
        $this->output->writeln('');
        if ($tests_fail->count() > 0) {
            if ($tests_skipped->count() > 0) {
                $this->output->writeln('Skipped tests:');
                foreach ($tests_skipped as $i => $test) {
                    $this->output->writeln(sprintf('<comment>%d.</comment> %s', ($i + 1), $test->getException()));
                }
            }
            $this->output->writeln('Failed tests:');
            foreach ($tests_fail as $i => $test) {
                $this->output->writeln(
                    sprintf('<error>%d.</error> %s', ($i + 1), $test->getException()->getMessage())
                );
                $this->output->writeln($test->getException()->getTraceAsString());
            }

            $this->output->writeln(
                sprintf(
                    '<error>FAILURES! Tests: %d, Skipped: %d, Assertions: %d, Failures: %d</error>',
                    $tests_success,
                    $tests_skipped->count(),
                    $asserts,
                    $tests_fail->count()
                )
            );
        } elseif ($tests_success > 0) {
            $style = new OutputFormatterStyle('black', 'green');
            $this->output->getFormatter()->setStyle('success', $style);
            $this->output->writeln(
                sprintf(
                    '<success>OK (Tests: %d, Skipped: %d, Asserts: %d)</success>',
                    $tests_success,
                    $tests_skipped->count(),
                    $asserts
                )
            );
        }
    }
}