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
        $this->printStatistics($statistics);
        if (count($statistics['fail']) > 0 || count($statistics['incomplete']) > 0) {
            $this->fail($statistics);
        } else {
            $this->success($statistics);
        }
    }

    /**
     * Output when one or more tests fail.
     *
     * @param StatisticsProcessor $statistics
     */
    private function fail(StatisticsProcessor $statistics)
    {
        $this->output->writeln(
            sprintf(
                '<error>FAILURES! A total of %d sets of tests with %d assertions have been processed</error>',
                $statistics->getCount(),
                $statistics['asserts']
            )
        );
        $this->output->writeln(
            sprintf(
                '<error>Success: %d, Skipped: %d, Incomplete: %d, Failures: %d</error>',
                $statistics['success'],
                count($statistics['skipped']),
                count($statistics['incomplete']),
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
    private function testOutput($title, $tag, StatisticsProcessor $tests)
    {
        $this->output->writeln($title);
        foreach ($tests as $i => $test) {
            $this->output->writeln(
                sprintf(
                    '<%5$s>%d.</%5$s> %s::%s()%s',
                    ($i + 1),
                    $test->getTestCaseEvent()->getClass(),
                    $test->getMethod(),
                    (($test->getDataSet() === 0) ? '' : ' with data set #' . ($i + 1)),
                    $tag
                )
            );
            $this->output->writeln($test->getExceptionMessage());
            if (!$test->isSkipped()) {
                $this->output->writeln($test->getStacktrace());
            }
            $this->output->writeln('');
        }
    }

    /**
     * Output when all test success.
     *
     * @param StatisticsProcessor $statistics
     */
    private function success(StatisticsProcessor $statistics)
    {
        $style = new OutputFormatterStyle('black', 'green');
        $this->output->getFormatter()->setStyle('success', $style);
        $this->output->writeln(
            sprintf(
                '<success>OK (Passed: %d, Skipped: %d, Asserts: %d). Total tests set: %d</success>',
                $statistics['success'],
                count($statistics['skipped']),
                $statistics['asserts'],
                $statistics->getCount()
            )
        );
    }

    /**
     * Print tests statistics.
     *
     * @param StatisticsProcessor $statistics
     */
    private function printStatistics(StatisticsProcessor $statistics)
    {
        $skipped_count = count($statistics['skipped']);
        $incomplete_count = count($statistics['incomplete']);
        $fail_count = count($statistics['fail']);
        if ($skipped_count > 0) {
            $style = new OutputFormatterStyle('black', 'yellow');
            $this->output->getFormatter()->setStyle('skipped', $style);
            $this->testOutput('Skipped tests:', 'skipped', $statistics['skipped']);
        }
        if ($incomplete_count > 0) {
            $style = new OutputFormatterStyle('white', 'blue');
            $this->output->getFormatter()->setStyle('incomplete', $style);
            $this->testOutput('Incomplete tests:', 'incomplete', $statistics['incomplete']);
        }
        if ($fail_count > 0) {
            $this->testOutput('Failed tests:', 'error', $statistics['fail']);
        }
    }
}
