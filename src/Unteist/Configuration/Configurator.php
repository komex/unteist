<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Configuration;

use Monolog\Handler\HandlerInterface;
use Monolog\Logger;
use Symfony\Component\Config\Definition\Processor as DefinitionProcessor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;
use Unteist\Console\Formatter;
use Unteist\Processor\Processor;
use Unteist\Strategy\Context;
use Unteist\Strategy\StrategyInterface;

/**
 * Class Configurator
 *
 * @package Unteist\Configurator
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class Configurator
{
    /**
     * @var ContainerBuilder
     */
    private $container;
    /**
     * @var Formatter
     */
    private $formatter;
    /**
     * @var array
     */
    private $config = [];
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;
    /**
     * @var array
     */
    private $configs = [];
    /**
     * @var string
     */
    private $suite;

    /**
     * Prepare configuration loader.
     */
    public function __construct(EventDispatcherInterface $dispatcher, Formatter $formatter)
    {
        $this->container = new ContainerBuilder();
        $this->dispatcher = $dispatcher;
        $this->formatter = $formatter;
        $locator = new FileLocator(join(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..']));
        $loader = new YamlFileLoader($this->container, $locator);
        $loader->load('services.yml');
    }

    /**
     * Load config from specified file.
     *
     * @param string $file Filename
     */
    public function getFromYaml($file)
    {
        $config = Yaml::parse($file);
        if (is_array($config)) {
            array_push($this->configs, $config);
        }
    }

    /**
     * Load config from Console Input.
     *
     * @param InputInterface $input
     */
    public function getFromInput(InputInterface $input)
    {
        $this->suite = $input->getArgument('suite');
        $config = [];
        $processes = $input->getOption('processes');
        if ($processes !== null) {
            $config['processes'] = $processes;
        }
        $report_dir = $input->getOption('report-dir');
        if ($report_dir !== null) {
            $config['report_dir'] = $report_dir;
        }
        array_push($this->configs, $config);
    }

    /**
     * Get configured tests processor.
     *
     * @return Processor
     */
    public function getProcessor()
    {
        if (empty($this->config)) {
            $this->processConfiguration();
        }
        $processor = new Processor($this->dispatcher, $this->getLogger(), $this->getContext());
        $processor->setProcesses($this->config['processes']);
        $this->registerReporter();
        $this->registerListeners();
        $processor->setSuite($this->getSuite());

        return $processor;
    }

    /**
     * Get suite files with tests.
     *
     * @return \ArrayObject
     */
    private function getSuite()
    {
        $files = new \ArrayObject();
        foreach ($this->config['source'] as $source) {
            $finder = new Finder();
            $finder->ignoreUnreadableDirs()->files();
            $finder->in($source['in'])->name($source['name']);
            if (!empty($source['notName'])) {
                $finder->notName($source['notName']);
            }
            if (!empty($source['exclude'])) {
                $finder->exclude($source['exclude']);
            }
            /** @var \SplFileInfo $file */
            foreach ($finder as $file) {
                $real_path = $file->getRealPath();
                if (!$files->offsetExists($real_path)) {
                    $files[$real_path] = $file;
                }
            }
        }
        // Output information and progress bar
        $this->formatter->start($files->count());

        return $files;
    }

    /**
     * Get configured context.
     *
     * @return Context
     */
    private function getContext()
    {
        /** @var StrategyInterface $error */
        $error = $this->container->get($this->config['context']['error']);
        /** @var StrategyInterface $failure */
        $failure = $this->container->get($this->config['context']['failure']);
        /** @var StrategyInterface $incomplete */
        $incomplete = $this->container->get($this->config['context']['incomplete']);
        /** @var StrategyInterface $skip */
        $skip = $this->container->get($this->config['context']['skip']);

        return new Context($error, $failure, $incomplete, $skip);
    }

    /**
     * Get configured logger.
     *
     * @return Logger
     */
    private function getLogger()
    {
        /** @var Logger $logger */
        $logger = $this->container->get('logger');
        if ($this->config['logger']['enabled']) {
            foreach ($this->config['logger']['handlers'] as $service) {
                /** @var HandlerInterface $handler */
                $handler = $this->container->get($service);
                $logger->pushHandler($handler);
            }
        } else {
            /** @var HandlerInterface $handler */
            $handler = $this->container->get('logger.handler.null');
            $logger->pushHandler($handler);
        }

        return $logger;
    }

    /**
     * Register custom event listeners.
     */
    private function registerListeners()
    {
        foreach ($this->config['listeners'] as $service) {
            /** @var EventSubscriberInterface $listener */
            $listener = $this->container->get($service);
            $this->dispatcher->addSubscriber($listener);
        }
    }

    /**
     * Register a reporter.
     */
    private function registerReporter()
    {
        if ($this->config['report_dir'] !== null) {
            $this->container->setParameter('report.dir', $this->config['report_dir']);
            /** @var EventSubscriberInterface $listener */
            $listener = $this->container->get('reporter');
            $this->dispatcher->addSubscriber($listener);
        }
    }

    /**
     * Process all configuration.
     */
    private function processConfiguration()
    {
        $processor = new DefinitionProcessor();
        $config = $processor->processConfiguration(new ConfigurationValidator(), $this->configs);
        $this->config = $this->getSuiteConfig($config);
    }

    /**
     * Get result config.
     *
     * @param array $config All config
     *
     * @return array
     */
    private function getSuiteConfig(array $config)
    {
        if (isset($config['suites'][$this->suite])) {
            $suite = $config['suites'][$this->suite];
            if (!empty($suite['report_dir'])) {
                $config['report_dir'] = $suite['report_dir'];
            }
            if (isset($suite['context'])) {
                $config['context'] = $suite['context'];
            }
            $config['source'] = $suite['source'];
        } else {
            $config['source'] = [
                ['in' => '.', 'name' => $this->suite, 'notName' => '', 'exclude' => []],
            ];
        }
        unset($config['suites']);

        return $config;
    }
}
