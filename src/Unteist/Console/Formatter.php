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
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Unteist\Event\EventStorage;
use Unteist\Event\MethodEvent;
use Unteist\Event\TestCaseEvent;
use Unteist\Report\Statistics\StatisticsProcessor;

/**
 * Class Formatter
 *
 * @package Unteist\Console
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class Formatter implements EventSubscriberInterface
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
     * @var float
     */
    private $started;
    /**
     * @var StatisticsProcessor
     */
    private $statistics;

    /**
     * @param OutputInterface $output
     * @param ProgressHelper $progress
     */
    public function __construct(OutputInterface $output, ProgressHelper $progress)
    {
        $this->output = $output;
        $this->progress = $progress;
        $this->statistics = new StatisticsProcessor();
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * array('eventName' => 'methodName')
     *  * array('eventName' => array('methodName', $priority))
     *  * array('eventName' => array(array('methodName1', $priority), array('methodName2'))
     *
     * @return array The event names to listen to
     *
     * @api
     */
    public static function getSubscribedEvents()
    {
        return [
            EventStorage::EV_METHOD_FINISH => 'method',
            EventStorage::EV_CASE_FILTERED => 'advance',
            EventStorage::EV_AFTER_CASE => 'afterCase',
            EventStorage::EV_APP_FINISHED => 'finish',
        ];
    }

    public function method(MethodEvent $event)
    {

    }

    /**
     * Listener on TestCase finish.
     */
    public function afterCase(TestCaseEvent $event)
    {
        $this->advance();
        $this->statistics->addTestCaseEvent($event);
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
        $this->started = microtime(true);
    }

    /**
     * Increase progress bar.
     */
    public function advance()
    {
        $this->progress->advance();
    }

    /**
     * Display result information.
     */
    public function finish()
    {
        $time = (microtime(true) - $this->started);
        $this->progress->finish();
        $this->output->writeln(sprintf('Time: <comment>%F</comment> seconds.', $time));
        $this->output->writeln('');
        $this->printStatistics($this->statistics);
        if (count($this->statistics['fail']) > 0 || count($this->statistics['incomplete']) > 0) {
            $this->fail($this->statistics);
        } else {
            $this->success($this->statistics);
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
     * @param StatisticsProcessor|MethodEvent[] $methods
     */
    private function testOutput($title, $tag, StatisticsProcessor $methods)
    {
        $this->output->writeln($title);
        foreach ($methods as $i => $method) {
            $this->output->writeln(
                sprintf(
                    '<%5$s>%d.</%5$s> %s::%s()%s',
                    ($i + 1),
                    $method->getClass(),
                    $method->getMethod(),
                    (($method->getDataSet() === 0) ? '' : ' with data set #' . ($i + 1)),
                    $tag
                )
            );
            $this->output->writeln($method->getExceptionMessage());
            if (!$method->isSkipped()) {
//                $this->output->writeln($method->getStacktrace());
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
