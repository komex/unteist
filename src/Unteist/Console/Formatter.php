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
use Unteist\Report\Statistics\StatisticsProcessor;

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
     * @param StatisticsProcessor $statistics
     */
    public function finish($time, StatisticsProcessor $statistics)
    {
        $this->progress->finish();
        $this->output->writeln(sprintf('Time: <comment>%F</comment> seconds.', $time));
        $this->output->writeln('');
        if (count($statistics['fail']) > 0) {
            $this->fail($statistics);
        } elseif ($statistics['success'] > 0) {
            $this->success($statistics);
        }
    }

    /**
     * Output when one or more tests fail.
     *
     * @param StatisticsProcessor $statistics
     */
    protected function fail(StatisticsProcessor $statistics)
    {
        $skipped_count = count($statistics['skipped']);
        if ($skipped_count > 0) {
            $style = new OutputFormatterStyle('black', 'yellow');
            $this->output->getFormatter()->setStyle('skipped', $style);
            $this->testOutput('Skipped tests:', 'skipped', $statistics['skipped']);
            $this->output->writeln('');
        }
        $this->testOutput('Failed tests:', 'error', $statistics['fail']);

        $this->output->writeln('');
        $this->output->writeln(
            sprintf(
                '<error>FAILURES! Tests: %d, Skipped: %d, Assertions: %d, Failures: %d</error>',
                $statistics['success'],
                $skipped_count,
                $statistics['asserts'],
                count($statistics['fail'])
            )
        );
    }

    /**
     * Print failed or skipped tests with stack trace.
     *
     * @param string $title Group title
     * @param string $tag Tag name for color output
     * @param StatisticsProcessor|TestEvent[] $tests
     */
    protected function testOutput($title, $tag, StatisticsProcessor $tests)
    {
        $this->output->writeln($title);
        foreach ($tests as $i => $test) {
            $this->output->writeln(
                sprintf('<%3$s>%d.</%3$s> %s', ($i + 1), $test->getException(), $tag)
            );
            //@todo: Output exception stack trace
        }
    }

    /**
     * Output when all test success.
     *
     * @param StatisticsProcessor $statistics
     */
    protected function success(StatisticsProcessor $statistics)
    {
        $style = new OutputFormatterStyle('black', 'green');
        $this->output->getFormatter()->setStyle('success', $style);
        $this->output->writeln(
            sprintf(
                '<success>OK (Tests: %d, Skipped: %d, Asserts: %d)</success>',
                $statistics['success'],
                count($statistics['skipped']),
                $statistics['asserts']
            )
        );
    }
}
