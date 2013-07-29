<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Console;


use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\ProgressHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Finder\Finder;
use Unteist\Event\EventStorage;
use Unteist\Event\TestCaseEvent;
use Unteist\Event\TestEvent;
use Unteist\Filter\ClassFilter;
use Unteist\Filter\MethodsFilter;
use Unteist\Processor\Processor;
use Unteist\Strategy\Context;

/**
 * Class RunCommand
 *
 * @package Unteist\Console
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class RunCommand extends Command
{
    /**
     * @var float
     */
    protected $started;
    /**
     * @var int
     */
    protected $asserts = 0;
    /**
     * @var int
     */
    protected $tests_success = 0;
    /**
     * @var \SplDoublyLinkedList|TestEvent[]
     */
    protected $tests_skipped;
    /**
     * @var \SplDoublyLinkedList|TestEvent[]
     */
    protected $tests_fail;
    /**
     * @var ProgressHelper
     */
    protected $progress;
    /**
     * @var OutputInterface
     */
    protected $output;
    /**
     * @var array
     */
    protected $log_levels = [
        'DEBUG' => Logger::DEBUG,
        'INFO' => Logger::INFO,
        'NOTICE' => Logger::NOTICE,
        'WARNING' => Logger::WARNING,
        'ERROR' => Logger::ERROR,
        'CRITICAL' => Logger::CRITICAL,
    ];

    /**
     * Listener on TestCase finish.
     */
    public function afterCase(TestCaseEvent $event)
    {
        $this->progress->advance();
        $this->asserts += $event->getAsserts();
    }

    /**
     * Increase success tests counter
     */
    public function successTest()
    {
        $this->tests_success++;
    }

    /**
     * Increase skipped tests counter
     *
     * @param TestEvent $event
     */
    public function skippedTest(TestEvent $event)
    {
        $this->tests_skipped->push($event);
    }

    /**
     * Increase fail tests counter
     *
     * @param TestEvent $event
     */
    public function failTest(TestEvent $event)
    {
        $this->tests_fail->push($event);
    }

    /**
     * Listener on application finish.
     */
    public function finish()
    {
        $time = (microtime(true) - $this->started);
        $this->progress->finish();
        $this->output->writeln(sprintf('Time: <comment>%F</comment> seconds.', $time));
        $this->output->writeln('');
        if ($this->tests_fail->count() > 0) {
            if ($this->tests_skipped->count() > 0) {
                $this->output->writeln('Skipped tests:');
                foreach ($this->tests_skipped as $i => $test) {
                    $this->output->writeln(sprintf('<comment>%d.</comment> %s', ($i + 1), $test->getException()));
                }
            }
            $this->output->writeln('Failed tests:');
            foreach ($this->tests_fail as $i => $test) {
                $this->output->writeln(
                    sprintf('<error>%d.</error> %s', ($i + 1), $test->getException()->getMessage())
                );
                $this->output->writeln($test->getException()->getTraceAsString());
            }

            $this->output->writeln(
                sprintf(
                    '<error>FAILURES! Tests: %d, Skipped: %d, Assertions: %d, Failures: %d</error>',
                    $this->tests_success,
                    $this->tests_skipped->count(),
                    $this->asserts,
                    $this->tests_fail->count()
                )
            );
        } elseif ($this->tests_success > 0) {
            $style = new OutputFormatterStyle('black', 'green');
            $this->output->getFormatter()->setStyle('success', $style);
            $this->output->writeln(
                sprintf(
                    '<success>OK (Tests: %d, Skipped: %d, Asserts: %d)</success>',
                    $this->tests_success,
                    $this->tests_skipped->count(),
                    $this->asserts
                )
            );
        }
    }

    /**
     * Configure command.
     */
    protected function configure()
    {
        $this->setName('run')->setDescription('Run tests stored in specified directory.');
        $this->addArgument('source', InputArgument::REQUIRED, 'Path to TestCase classes.');
        $this->addOption('processes', 'p', InputOption::VALUE_REQUIRED, 'Run test in N separated processes.', 1);
        $this->addOption('strategy', 's', InputOption::VALUE_REQUIRED, 'Assert strategy: STOP, IGNORE', 'STOP');
        $this->addOption(
            'log-level',
            'l',
            InputOption::VALUE_REQUIRED,
            sprintf('Logger level: OFF, %s.', join(', ', array_keys($this->log_levels))),
            'OFF'
        );
        $this->addOption('log-file', 'f', InputOption::VALUE_REQUIRED, 'Logger output file.', 'unteist.log');
    }

    /**
     * Start searching and executing tests.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->tests_skipped = new \SplDoublyLinkedList();
        $this->tests_fail = new \SplDoublyLinkedList();
        $output->writeln($this->getApplication()->getLongVersion());
        // Logger
        $logger = new Logger('unteist');
        if (isset($this->log_levels[$input->getOption('log-level')])) {
            $logger->pushHandler(
                new StreamHandler($input->getOption('log-file'), $this->log_levels[$input->getOption('log-level')])
            );
            $output->writeln(
                sprintf(
                    'The <info>%s</info> logs will be written to <comment>%s</comment>.',
                    $input->getOption('log-level'),
                    $input->getOption('log-file')
                )
            );
        } else {
            $logger->pushHandler(new NullHandler());
        }
        // Finder
        $finder = new Finder();
        $finder->files()->in($input->getArgument('source'))->name('*Test.php');
        // EventDispatcher
        $dispatcher = new EventDispatcher();
        // Processor
        $processor = new Processor($dispatcher, $logger);
        $processor->addClassFilter(new ClassFilter());
        $processor->addMethodsFilter(new MethodsFilter());
        $processor->setSuites($finder);
        $processor->setMaxProcs($input->getOption('processes'));
        switch ($input->getOption('strategy')) {
            case 'IGNORE':
                $processor->setStrategy(Context::STRATEGY_IGNORE_FAILS);
                break;
            default:
                $processor->setStrategy(Context::STRATEGY_STOP_ON_FAILS);
        }
        // Global variables
        $this->started = microtime(true);
        $this->progress = $this->getHelperSet()->get('progress');
        $this->output = $output;
        // Output information and progress bar
        $output->writeln(
            sprintf('Found <comment>%d</comment> %s.', $finder->count(), $finder->count() === 1 ? 'file' : 'files')
        );
        $this->progress->start($output, $finder->count());
        $this->progress->display();
        // Register listeners
        $dispatcher->addListener(EventStorage::EV_AFTER_CASE, [$this, 'afterCase']);
        $dispatcher->addListener(EventStorage::EV_TEST_SUCCESS, [$this, 'successTest']);
        $dispatcher->addListener(EventStorage::EV_TEST_SKIPPED, [$this, 'skippedTest']);
        $dispatcher->addListener(EventStorage::EV_TEST_FAIL, [$this, 'failTest']);
        $dispatcher->addListener(EventStorage::EV_APP_FINISHED, [$this, 'finish']);

        // Run tests
        return $processor->run();
    }

}