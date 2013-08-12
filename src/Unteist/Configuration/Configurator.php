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
use Unteist\Filter\ClassFilterInterface;
use Unteist\Filter\MethodsFilterInterface;
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
     * @var InputInterface
     */
    private $input;
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
     * Prepare configuration loader.
     */
    public function __construct(EventDispatcherInterface $dispatcher, InputInterface $input, Formatter $formatter)
    {
        $this->container = new ContainerBuilder();
        $this->dispatcher = $dispatcher;
        $this->input = $input;
        $this->formatter = $formatter;
        $this->loadServiceConfig(new FileLocator(join(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..'])));
        $this->loadServiceConfig(new FileLocator(realpath('.')));
        $this->loadFromYaml('./unteist.yml');
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
        if (!empty($this->config['groups'])) {
            array_unshift($this->config['filters']['methods'], 'filter.methods.group');
        }
        foreach ($this->config['filters']['class'] as $filter_id) {
            /** @var ClassFilterInterface $filter */
            $filter = $this->container->get($filter_id);
            $filter->setParams($this->config);
            $processor->addClassFilter($filter);
        }
        foreach ($this->config['filters']['methods'] as $filter_id) {
            /** @var MethodsFilterInterface $filter */
            $filter = $this->container->get($filter_id);
            $filter->setParams($this->config);
            $processor->addMethodsFilter($filter);
        }
        $processor->setSuite($this->getSuite());

        return $processor;
    }

    /**
     * Load service config from "unteist.services.yml".
     *
     * @param FileLocator $locator Where to find
     */
    private function loadServiceConfig(FileLocator $locator)
    {
        try {
            $loader = new YamlFileLoader($this->container, $locator);
            $loader->load('unteist.services.yml');
        } catch (\InvalidArgumentException $e) {
        }
    }

    /**
     * Load config from specified file.
     *
     * @param string $file Filename
     */
    private function loadFromYaml($file)
    {
        $config = Yaml::parse($file);
        if (is_array($config)) {
            array_push($this->configs, $config);
        }
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
                if (!$files->offsetExists($real_path) && substr($file->getFilename(), -8) === 'Test.php') {
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

        return new Context($error, $failure, $incomplete);
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
        $config = $this->getSuiteConfig($config);
        // Rewrite options from input
        $processes = $this->input->getOption('processes');
        if ($processes !== null) {
            $config['processes'] = $processes;
        }
        $report_dir = $this->input->getOption('report-dir');
        if ($report_dir !== null) {
            $config['report_dir'] = $report_dir;
        }
        $groups = $this->input->getOption('group');
        if (!empty($groups)) {
            $config['groups'] = $groups;
        }
        $this->config = $config;
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
        if (isset($config['suites'][$this->input->getArgument('suite')])) {
            $suite = $config['suites'][$this->input->getArgument('suite')];
            if (!empty($suite['report_dir'])) {
                $config['report_dir'] = $suite['report_dir'];
            }
            if (isset($suite['context'])) {
                $config['context'] = $suite['context'];
            }
            if (isset($suite['filters'])) {
                $config['filters'] = $suite['filters'];
            }
            if (empty($config['groups'])) {
                $config['groups'] = $suite['groups'];
            }
            $config['source'] = $suite['source'];
        } else {
            $config['source'] = [];
            $suite = new \SplFileInfo($this->input->getArgument('suite'));
            if ($suite->isFile() && $suite->isReadable()) {
                $config['source'][] = [
                    'in' => ($suite->getPath() ? : '.'),
                    'name' => $suite->getFilename(),
                    'notName' => '',
                    'exclude' => []
                ];
            } elseif ($suite->isDir()) {
                $config['source'][] = [
                    'in' => $suite->getRealPath(),
                    'name' => '*Test.php',
                    'notName' => '',
                    'exclude' => []
                ];
            }
        }
        unset($config['suites']);

        return $config;
    }
}
