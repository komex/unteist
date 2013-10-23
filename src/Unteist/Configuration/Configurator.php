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
use Symfony\Component\Finder\Finder;
use Unteist\Processor\Processor;

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
     */
    public function __construct(ContainerBuilder $container, InputInterface $input)
    {
        $this->container = $container;
        $this->input = $input;

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
        $this->getDefinition('runner');
        if (intval($this->config['processes'], 10) === 1) {
            $serviceID = 'processor';
            $definition = $this->getDefinition($serviceID);
        } else {
            $serviceID = 'processor.multi';
            $definition = $this->getDefinition($serviceID);
            $definition->addMethodCall('setProcesses', [$this->config['processes']]);
        }
        $definition->addMethodCall('setErrorHandler', [$this->config['context']['levels']]);

        if (!empty($this->config['groups'])) {
            array_unshift($this->config['filters']['methods'], 'filter.methods.group');
        }
        foreach ($this->config['filters']['class'] as $filterId) {
            $definition->addMethodCall('addClassFilter', [new Reference($filterId)]);
        }
        foreach ($this->config['filters']['methods'] as $filterId) {
            $definition->addMethodCall('addMethodsFilter', [new Reference($filterId)]);
        }
        $this->container->compile();


        return $this->container->get($serviceID);
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
     * Get suite files with tests.
     *
     * @return \ArrayObject
     */
    public function getFiles()
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
                $realPath = $file->getRealPath();
                if (!$files->offsetExists($realPath) && substr($file->getFilename(), -8) === 'Test.php') {
                    $files[$realPath] = $file;
                }
            }
        }

        return $files;
    }

    /**
     * Get definition of processor.
     */
    private function getDefinition($name)
    {
        $definition = $this->container->getDefinition($name);
        $definition->addArgument($this->container);
        $definition->setSynthetic(false);

        return $definition;
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
        $reportDir = $this->input->getOption('report-dir');
        if ($reportDir !== null) {
            $config['report_dir'] = $reportDir;
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
        $definition->setSynthetic(false);

        return $definition;
    }

    /**
     * Get configured context.
     *
     * @return Definition
     */
    private function configureContext()
    {
        $context = $this->config['context'];
        $definition = $this->container->getDefinition('context');
        $definition->addMethodCall('setErrorStrategy', [new Reference($context['error'])]);
        $definition->addMethodCall('setFailureStrategy', [new Reference($context['failure'])]);
        $definition->addMethodCall('setIncompleteStrategy', [new Reference($context['incomplete'])]);
        $definition->addMethodCall('setBeforeCaseStrategy', [new Reference($context['beforeCase'])]);
        $definition->addMethodCall('setBeforeTestStrategy', [new Reference($context['beforeTest'])]);
        $definition->addMethodCall('setAfterTestStrategy', [new Reference($context['afterTest'])]);
        $definition->addMethodCall('setAfterCaseStrategy', [new Reference($context['afterCase'])]);
        foreach ($context['associations'] as $class => $strategyId) {
            $definition->addMethodCall('associateException', [$class, new Reference($strategyId)]);
        }
        $definition->setSynthetic(false);

        return $definition;
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
}
