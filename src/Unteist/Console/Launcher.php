<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Console;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\IniFileLoader;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Yaml\Yaml;
use Unteist\Configuration\Configurator;
use Unteist\Event\EventStorage;
use Unteist\Event\TestCaseEvent;
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
     * @var EventDispatcherInterface
     */
    protected $dispatcher;
    /**
     * @var ContainerBuilder
     */
    protected $container;
    /**
     * @var float
     */
    private $started;
    /**
     * @var StatisticsProcessor
     */
    private $statistics;
    /**
     * @var Formatter
     */
    private $formatter;

    /**
     * Configure Launcher
     */
    public function __construct()
    {
        $this->statistics = new StatisticsProcessor();
        $this->dispatcher = new EventDispatcher();
        $this->container = new ContainerBuilder();
        parent::__construct();
    }

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
     * Load services definition to ContainerBuilder.
     *
     * @param ContainerBuilder $container Save definition to this container
     * @param FileLocatorInterface $locator Where to find config file
     * @param string $filename The name of config file
     */
    protected function loadServicesDefinition(ContainerBuilder $container, FileLocatorInterface $locator, $filename)
    {
        try {
            $loaders = [
                new YamlFileLoader($container, $locator),
                new XmlFileLoader($container, $locator),
                new IniFileLoader($container, $locator),
                new PhpFileLoader($container, $locator),
            ];
            $loaderResolver = new LoaderResolver($loaders);
            $loader = new DelegatingLoader($loaderResolver);
            $loader->load($filename);
        } catch (\InvalidArgumentException $e) {
        }
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
        $this->addOption(
            'group',
            'g',
            InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
            'Filter tests by groups.'
        );
    }

    /**
     * Load all custom configuration to Configurator.
     *
     * @param Configurator $configurator
     */
    protected function loadConfig(Configurator $configurator)
    {
        $config = Yaml::parse('./unteist.yml');
        $configurator->addConfig(is_array($config) ? $config : []);
        $this->loadServicesDefinition($this->container, new FileLocator(realpath('.')), 'unteist.services.yml');
    }

    /**
     * Start searching and executing tests.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int Status code
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln($this->getApplication()->getLongVersion());
        /** @var ProgressHelper $progress */
        $progress = $this->getHelperSet()->get('progress');
        // Formatter
        $this->formatter = new Formatter($output, $progress);
        // Configurator
        $configurator = new Configurator($this->container, $this->dispatcher, $input, $this->formatter);
        $this->loadConfig($configurator);
        // Processor
        $processor = $configurator->getProcessor();
        // Global variables
        $this->started = microtime(true);
        // Register listeners
        $this->registerListeners($this->dispatcher);

        // Run tests
        return $processor->run();
    }

    /**
     * Register all listeners for getting statistics.
     *
     * @param EventDispatcherInterface $dispatcher General dispatcher
     */
    private function registerListeners(EventDispatcherInterface $dispatcher)
    {
        $dispatcher->addListener(EventStorage::EV_AFTER_CASE, [$this, 'afterCase']);
        $dispatcher->addListener(EventStorage::EV_APP_FINISHED, [$this, 'finish']);
        $dispatcher->addListener(EventStorage::EV_CASE_FILTERED, [$this, 'incProgress']);
    }
}
