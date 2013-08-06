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
use Symfony\Component\Console\Helper\ProgressHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Finder\Finder;
use Unteist\Event\EventStorage;
use Unteist\Event\TestCaseEvent;
use Unteist\Filter\ClassFilter;
use Unteist\Filter\MethodsFilter;
use Unteist\Processor\Processor;
use Unteist\Report\Statistics\StatisticsProcessor;
use Unteist\Report\Twig\TwigReport;
use Unteist\Strategy\Context;
use Unteist\Strategy\IncompleteTestStrategy;
use Unteist\Strategy\SkipTestStrategy;
use Unteist\Strategy\TestFailStrategy;

/**
 * Class Launcher
 *
 * @package Unteist\Console
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class Launcher extends Command
{
    /**
     * @var float
     */
    protected $started;
    /**
     * @var StatisticsProcessor
     */
    protected $statistics;
    /**
     * @var Formatter
     */
    protected $formatter;
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
        $this->formatter->advance();
        $this->statistics->addTestCaseEvent($event);
    }

    /**
     * Listener on application finish.
     */
    public function finish()
    {
        $time = (microtime(true) - $this->started);
        $this->formatter->finish($time, $this->statistics);
    }

    /**
     * Configure command.
     */
    protected function configure()
    {
        $this->setName('run')->setDescription('Run tests stored in specified directory.');
        $this->addArgument('source', InputArgument::REQUIRED, 'Path to TestCase classes.');
        $this->addOption('processes', 'p', InputOption::VALUE_REQUIRED, 'Run test in N separated processes.', 1);
        $this->addOption('strategy', 's', InputOption::VALUE_REQUIRED, 'Assert strategy: STOP, IGNORE.', 'STOP');
        $this->addOption(
            'log-level',
            'l',
            InputOption::VALUE_REQUIRED,
            sprintf('Logger level: %s.', join(', ', array_keys($this->log_levels)))
        );
        $this->addOption('log-file', 'f', InputOption::VALUE_REQUIRED, 'Logger output file.', 'unteist.log');
        $this->addOption('report-dir', 'r', InputOption::VALUE_REQUIRED, 'Report output directory.');
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
        $this->statistics = new StatisticsProcessor();
        /** @var ProgressHelper $progress */
        $progress = $this->getHelperSet()->get('progress');
        $this->formatter = new Formatter($output, $progress);
        $output->writeln($this->getApplication()->getLongVersion());
        // Finder
        $finder = new Finder();
        $finder->files()->in($input->getArgument('source'))->name('*Test.php');
        // EventDispatcher
        $dispatcher = new EventDispatcher();
        // Twig report
        if ($input->getOption('report-dir')) {
            $dispatcher->addSubscriber(new TwigReport($input->getOption('report-dir')));
        }
        // Logger
        $logger = $this->getLogger($input->getOption('log-level'), $input->getOption('log-file'));
        // Context
        $context = $this->getContext();
        // Processor
        $processor = new Processor($dispatcher, $logger, $context);
        $processor->addClassFilter(new ClassFilter());
        $processor->addMethodsFilter(new MethodsFilter());
        $processor->setSuites($finder);
        $processor->setProcesses($input->getOption('processes'));
        // Global variables
        $this->started = microtime(true);
        // Output information and progress bar
        $this->formatter->start($finder->count());

        // Register listeners
        $this->registerListeners($dispatcher);

        // Run tests
        return $processor->run();
    }

    /**
     * Get configured logger.
     *
     * @param string $level Log-level
     * @param string $file Output file
     *
     * @return Logger
     */
    protected function getLogger($level, $file)
    {
        $logger = new Logger('unteist');
        if (isset($this->log_levels[$level])) {
            $log_level = $this->log_levels[$level];
            $logger->pushHandler(
                new StreamHandler($file, $log_level)
            );
            $this->formatter->loggerInformation($level, $file);
        } else {
            $logger->pushHandler(new NullHandler());
        }

        return $logger;
    }

    /**
     * Get configured context.
     *
     * @return Context
     */
    protected function getContext()
    {
        $fail_strategy = new TestFailStrategy();

        return new Context($fail_strategy, $fail_strategy, new IncompleteTestStrategy(), new SkipTestStrategy());
    }

    /**
     * Gerister all listeners for getting statistics.
     *
     * @param EventDispatcher $dispatcher General dispatcher
     */
    protected function registerListeners(EventDispatcher $dispatcher)
    {
        $dispatcher->addListener(EventStorage::EV_AFTER_CASE, [$this, 'afterCase']);
        $dispatcher->addListener(EventStorage::EV_APP_FINISHED, [$this, 'finish']);
    }
}
