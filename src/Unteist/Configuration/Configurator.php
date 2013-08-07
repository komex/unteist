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
use Symfony\Component\Yaml\Yaml;
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
    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->container = new ContainerBuilder();
        $this->dispatcher = $dispatcher;
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

        return $processor;
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
        $this->config = $processor->processConfiguration(new ConfigurationValidator(), $this->configs);
        var_dump($this->config);
    }
}
