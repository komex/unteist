<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Console;


use Monolog\Logger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Finder\Finder;
use Unteist\Configuration\Configurator;
use Unteist\Event\EventStorage;
use Unteist\Event\TestCaseEvent;
use Unteist\Filter\ClassFilter;
use Unteist\Filter\MethodsFilter;
use Unteist\Report\Statistics\StatisticsProcessor;

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
     * Increase progress bar.
     */
    public function incProgress()
    {
        $this->formatter->advance();
    }

    /**
     * Listener on TestCase finish.
     */
    public function afterCase(TestCaseEvent $event)
    {
        $this->incProgress();
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
        $this->addArgument('suite', InputArgument::REQUIRED, 'Suite name in config file or path to TestCase classes.');
        $this->addOption('processes', 'p', InputOption::VALUE_REQUIRED, 'Run test in N separated processes.');
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
        $output->writeln($this->getApplication()->getLongVersion());
        $dispatcher = new EventDispatcher();
        $this->statistics = new StatisticsProcessor();
        /** @var ProgressHelper $progress */
        $progress = $this->getHelperSet()->get('progress');
        // Formatter
        $this->formatter = new Formatter($output, $progress);
        // Configurator
        $configurator = new Configurator($dispatcher, $this->formatter);
        $configurator->getFromYaml('./unteist.yml');
        $configurator->getFromInput($input);
        // Processor
        $processor = $configurator->getProcessor();
        // Global variables
        $this->started = microtime(true);
        // Register listeners
        $this->registerListeners($dispatcher);

        // Run tests
        return $processor->run();
    }

    /**
     * Register all listeners for getting statistics.
     *
     * @param EventDispatcher $dispatcher General dispatcher
     */
    private function registerListeners(EventDispatcher $dispatcher)
    {
        $dispatcher->addListener(EventStorage::EV_AFTER_CASE, [$this, 'afterCase']);
        $dispatcher->addListener(EventStorage::EV_APP_FINISHED, [$this, 'finish']);
        $dispatcher->addListener(EventStorage::EV_CASE_FILTERED, [$this, 'incProgress']);
    }
}
