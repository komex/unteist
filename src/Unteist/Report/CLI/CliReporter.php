<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Report\CLI;

use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\ProgressHelper;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Unteist\Event\EventStorage;
use Unteist\Event\MethodEvent;

/**
 * Class CliReport
 *
 * @package Unteist\Console
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class CliReporter extends ContainerAware implements EventSubscriberInterface
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
     * @var float
     */
    private $time = 0;
    /**
     * @var int
     */
    private $asserts = 0;
    /**
     * @var int
     */
    private $passed = 0;
    /**
     * @var MethodEvent[]
     */
    private $incomplete;
    /**
     * @var MethodEvent[]
     */
    private $failed;
    /**
     * @var MethodEvent[]
     */
    private $skipped;
    /**
     * @var int
     */
    private $case_count = 0;

    public function __construct()
    {
        $this->progress = new ProgressHelper();
        $this->progress->setFormat(' %percent%% [%bar%] Elapsed: %elapsed%');
        $this->failed = new \ArrayObject();
        $this->skipped = new \ArrayObject();
        $this->incomplete = new \ArrayObject();
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
            EventStorage::EV_APP_STARTED => 'start',
            EventStorage::EV_METHOD_DONE => 'methodDone',
            EventStorage::EV_METHOD_FAILED => 'methodFailed',
            EventStorage::EV_METHOD_SKIPPED => 'methodSkipped',
            EventStorage::EV_METHOD_INCOMPLETE => 'methodIncomplete',
            EventStorage::EV_CASE_FILTERED => 'advance',
            EventStorage::EV_AFTER_CASE => 'afterCase',
            EventStorage::EV_APP_FINISHED => 'finish',
        ];
    }

    /**
     * @return ProgressHelper
     */
    public function getProgress()
    {
        return $this->progress;
    }

    /**
     * Start reporter.
     */
    public function start()
    {
        /** @var OutputInterface $output */
        $output = $this->container->get('cli.output');;
        $this->output = $output;
        $this->progress->start($output, count($this->container->getParameter('suites')));
        $this->progress->display();
        $this->started = microtime(true);
    }

    /**
     * Method successful done.
     *
     * @param MethodEvent $event
     */
    public function methodDone(MethodEvent $event)
    {
        $this->passed++;
        $this->asserts += $event->getAsserts();
        $this->time += $event->getTime();
    }

    /**
     * Method was failed.
     *
     * @param MethodEvent $event
     */
    public function methodFailed(MethodEvent $event)
    {
        $this->failed->append($event);
        $this->asserts += $event->getAsserts();
        $this->time += $event->getTime();
    }

    /**
     * Method was skipped.
     *
     * @param MethodEvent $event
     */
    public function methodSkipped(MethodEvent $event)
    {
        $this->skipped->append($event);
        $this->asserts += $event->getAsserts();
        $this->time += $event->getTime();
    }

    /**
     * Method marked as incomplete.
     *
     * @param MethodEvent $event
     */
    public function methodIncomplete(MethodEvent $event)
    {
        $this->incomplete->append($event);
        $this->asserts += $event->getAsserts();
        $this->time += $event->getTime();
    }

    /**
     * Test case was finished.
     */
    public function afterCase()
    {
        $this->advance();
        $this->case_count++;
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
        $this->output->writeln(sprintf('Execution time: <comment>%F</comment> seconds.', $this->time));
        $this->output->writeln(sprintf('Total time: <comment>%F</comment> seconds.', $time));
        $this->output->writeln('');
        $style = new OutputFormatterStyle('black', 'yellow');
        $this->output->getFormatter()->setStyle('skipped', $style);
        if ($this->incomplete->count() > 0) {
            $style = new OutputFormatterStyle('white', 'blue');
            $this->output->getFormatter()->setStyle('incomplete', $style);
            $this->output->writeln('Incomplete tests:');
            foreach ($this->incomplete as $i => $method) {
                $this->printMethod(($i + 1), $method, 'incomplete');
            }
        }
        if ($this->failed->count() > 0) {
            $this->output->writeln('Failed tests:');
            foreach ($this->failed as $i => $method) {
                $this->printMethod(($i + 1), $method, 'error');
            }
            $this->fail();
        } else {
            $this->success();
        }
    }

    /**
     * Print information about method.
     *
     * @param int $no Index number
     * @param MethodEvent $method
     * @param string $tag Tag for highlight index number.
     */
    private function printMethod($no, MethodEvent $method, $tag)
    {
        $this->output->writeln(
            sprintf(
                '<%5$s>%d.</%5$s> %s::%s()%s',
                $no,
                $method->getClass(),
                $method->getMethod(),
                (($method->getDataSet() === 0) ? '' : ' with data set #' . $no),
                $tag
            )
        );
        $this->output->writeln(sprintf('[%s]: %s', $method->getException(), $method->getExceptionMessage()));
        $file = $method->getFile();
        $line = $method->getLine();
        foreach ($method->getTrace() as $trace) {
            $this->output->write(sprintf('- %s%s%s()', $trace['class'], $trace['type'], $trace['function']));
            $output = sprintf(' at <info>%s</info>:<comment>%d</comment>', $file, $line);
            $this->output->writeln($output);
            if (isset($trace['file'])) {
                $file = $trace['file'];
                $line = $trace['line'];
            }
        }
        $this->printSkippedTests($method);
        $this->output->writeln('');
    }

    /**
     * Print skipped tests for specified method.
     *
     * @param MethodEvent $method
     */
    private function printSkippedTests(MethodEvent $method)
    {
        $index = 1;
        foreach ($this->skipped as $skipped) {
            if ($method->getClass() !== $skipped->getClass()) {
                continue;
            }
            $depends = $skipped->getDepends();
            if (in_array($method->getMethod(), $depends)) {
                $this->output->writeln(
                    "\t" . sprintf('<skipped>%d.</skipped> %s()', $index, $skipped->getMethod())
                );
            }
            $index++;
        }
    }

    /**
     * Output when one or more tests fail.
     */
    private function fail()
    {
        $this->output->writeln(
            sprintf(
                '<error>FAILURES! A total of %d sets of tests with %d assertions have been processed</error>',
                $this->case_count,
                $this->asserts
            )
        );
        $this->output->writeln(
            sprintf(
                '<error>Success: %d, Skipped: %d, Incomplete: %d, Failures: %d</error>',
                $this->passed,
                count($this->skipped),
                count($this->incomplete),
                count($this->failed)
            )
        );
    }

    /**
     * Output when all test success.
     */
    private function success()
    {
        $style = new OutputFormatterStyle('black', 'green');
        $this->output->getFormatter()->setStyle('success', $style);
        $this->output->writeln(
            sprintf(
                '<success>OK (Passed: %d, Skipped: %d, Asserts: %d). Total tests set: %d</success>',
                $this->passed,
                $this->skipped->count(),
                $this->asserts,
                $this->case_count
            )
        );
    }
}
