<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Configuration;

use Symfony\Component\Config\Definition\Processor as DefinitionProcessor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Finder\Finder;
use Unteist\Event\Connector;
use Unteist\Filter\ClassFilterInterface;
use Unteist\Filter\MethodsFilterInterface;
use Unteist\Processor\MultiProcessor;
use Unteist\Processor\Processor;
use Unteist\Report\CLI\CliReport;

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
     * @var CliReport
     */
    private $report;
    /**
     * @var array
     */
    private $config = [];
    /**
     * @var array
     */
    private $configs = [];

    /**
     * Prepare configuration loader.
     *
     * @param ContainerBuilder $container
     * @param InputInterface $input
     * @param CliReport $report
     */
    public function __construct(
        ContainerBuilder $container,
        InputInterface $input,
        CliReport $report
    ) {
        $this->container = $container;
        $this->input = $input;
        $this->report = $report;

        $directory = realpath(join(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..']));
        $loader = new YamlFileLoader($this->container, new FileLocator($directory));
        $loader->load('services.yml');
    }

    /**
     * Add tests config.
     *
     * @param array $config
     */
    public function addConfig(array $config)
    {
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
        $this->registerReporter();
        $this->registerListeners($this->config['listeners']);
        $this->configureLogger();
        $this->configureContext();
        if ($this->config['processes'] === 1) {
            $processor = new Processor(
                $this->container,
                $this->getSuite()
            );
        } else {
            $processor = new MultiProcessor(
                $this->container,
                $this->getSuite()
            );
            $processor->setProcesses($this->config['processes']);
            $processor->setConnector($this->getConnector());
        }
        $processor->setErrorTypes($this->config['context']['levels']);

        if (!empty($this->config['groups'])) {
            array_unshift($this->config['filters']['methods'], 'filter.methods.group');
        }
        foreach ($this->config['filters']['class'] as $filter_id) {
            /** @var ClassFilterInterface $filter */
            $filter = $this->container->get($filter_id);
            $processor->addClassFilter($filter);
        }
        foreach ($this->config['filters']['methods'] as $filter_id) {
            /** @var MethodsFilterInterface $filter */
            $filter = $this->container->get($filter_id);
            $filter->setParams($this->config);
            $processor->addMethodsFilter($filter);
        }

        return $processor;
    }

    /**
     * Load bootstrap file if exists.
     */
    public function loadBootstrap()
    {
        $this->includeFile('bootstrap');
    }

    /**
     * Load cleanup file if exists.
     */
    public function loadCleanUp()
    {
        $this->includeFile('cleanup');
    }

    /**
     * Include file by path in config file.
     *
     * @param string $name Parameter name
     */
    private function includeFile($name)
    {
        if ($this->container->hasParameter($name)) {
            $file = $this->container->getParameter($name);
            if (file_exists($file)) {
                include($file);
            }
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
            $config = $this->overwriteConfigFromSuite($config);
        } else {
            $config['source'] = [];
            array_push($config['source'], $this->getTestsSource(new \SplFileInfo($this->input->getArgument('suite'))));
        }
        unset($config['suites']);

        return $config;
    }

    /**
     * Get tests source config.
     *
     * @param \SplFileInfo $suite
     *
     * @throws \InvalidArgumentException If file or directory was not found.
     * @return array
     */
    private function getTestsSource(\SplFileInfo $suite)
    {
        if ($suite->isFile() && $suite->isReadable()) {
            $source = [
                'in' => ($suite->getPath() ? : '.'),
                'name' => $suite->getFilename(),
                'notName' => '',
                'exclude' => []
            ];
        } elseif ($suite->isDir()) {
            $source = [
                'in' => $suite->getRealPath(),
                'name' => '*Test.php',
                'notName' => '',
                'exclude' => []
            ];
        } else {
            throw new \InvalidArgumentException(
                sprintf('File or directory was not found (looking for "%s")', $suite->getFilename())
            );
        }

        return $source;
    }

    /**
     * Overwrite parameters in config by parameters in selected suite.
     *
     * @param array $config Original config
     *
     * @return array
     */
    private function overwriteConfigFromSuite(array $config)
    {
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
        if (isset($suite['groups'])) {
            $config['groups'] = $suite['groups'];
        }
        $config['source'] = $suite['source'];

        return $config;
    }

    /**
     * Get configured logger.
     *
     * @return Definition
     */
    private function configureLogger()
    {
        $definition = $this->container->getDefinition('logger');
        if ($this->config['logger']['enabled']) {
            $handlers = [];
            foreach ($this->config['logger']['handlers'] as $service) {
                array_push($handlers, $service);
            }
        } else {
            $handlers = ['logger.handler.null'];
        }

        foreach ($handlers as $handler) {
            $definition->addMethodCall('pushHandler', [new Reference($handler)]);
        }

        return $definition;
    }

    /**
     * Get configured context.
     *
     * @return Definition
     */
    private function configureContext()
    {
        $definition = $this->container->getDefinition('context');
        $definition->setArguments(
            [
                new Reference($this->config['context']['error']),
                new Reference($this->config['context']['failure']),
                new Reference($this->config['context']['incomplete'])
            ]
        );
        foreach ($this->config['context']['associations'] as $class => $strategy_id) {
            $definition->addMethodCall('associateException', [$class, new Reference($strategy_id)]);
        }

        return $definition;
    }

    /**
     * Get configured connector for multi processors working.
     *
     * @return Connector
     */
    private function getConnector()
    {
        if ($this->container->hasParameter('proxy_events')) {
            $proxy_events = (array)$this->container->getParameter('proxy_events');
        } else {
            $proxy_events = [];
        }
        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = $this->container->get('dispatcher');

        return new Connector($dispatcher, $proxy_events);
    }

    /**
     * Register a reporter.
     */
    private function registerReporter()
    {
        if ($this->config['report_dir'] !== null) {
            $this->container->setParameter('report.dir', $this->config['report_dir']);
            $this->registerListeners(['reporter']);
        }
    }

    /**
     * Register custom event listeners.
     *
     * @param array $listeners
     */
    private function registerListeners(array $listeners)
    {
        $definition = $this->container->getDefinition('dispatcher');
        foreach ($listeners as $service) {
            $definition->addMethodCall('addSubscriber', [new Reference($service)]);
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
                foreach ($source['notName'] as $name) {
                    $finder->notName($name);
                }
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
        $this->report->start($files->count());

        return $files;
    }
}
