<?php
/**
 * This file is a part of unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Configuration;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

/**
 * Class ConfigurationProcessor
 *
 * @package Unteist\Configuration
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class ConfigurationProcessor implements ExtensionInterface
{
    /**
     * @var string
     */
    private $suite;
    /**
     * @var array
     */
    private $config = [];

    /**
     * @param string $suite
     */
    public function __construct($suite)
    {
        $this->suite = $suite;
    }

    /**
     * @param array $groups
     */
    public function setGroups(array $groups)
    {
        if (!empty($groups)) {
            $this->config['groups'] = $groups;
        }
    }

    /**
     * @param int $processes
     */
    public function setProcesses($processes)
    {
        if ($processes !== null) {
            $this->config['processes'] = intval($processes);
        }
    }

    /**
     * Loads a specific configuration.
     *
     * @param array $config An array of configuration values
     * @param ContainerBuilder $container A ContainerBuilder instance
     *
     * @throws \InvalidArgumentException When provided tag is not defined in this extension
     *
     * @api
     */
    public function load(array $config, ContainerBuilder $container)
    {
        $processor = new Processor();
        $config = $processor->processConfiguration(new ConfigurationValidator(), $config);
        if ($this->suite !== null) {
            if (isset($config['suites'][$this->suite])) {
                $config = array_merge($config, $config['suites'][$this->suite]);
            } else {
                $config['source'] = [$this->getTestsSource(new \SplFileInfo($this->suite))];
            }
        }
        unset($config['suites']);
        $config = array_merge($config, $this->config);
        $container->setParameter('sources', $config['source']);
        $container->setParameter('context', $config['context']);
        $container->setParameter('processes', $config['processes']);
        $container->setParameter('logger.handlers', $config['logger']);
        $container->setParameter('filters', $config['filters']);
        $container->setParameter('groups', $config['groups']);
        $container->setParameter('bootstrap', empty($config['bootstrap']) ? null : $config['bootstrap']);
    }


    /**
     * Returns the namespace to be used for this extension (XML namespace).
     *
     * @return string The XML namespace
     *
     * @api
     */
    public function getNamespace()
    {
        return 'unteist';
    }

    /**
     * Returns the base path for the XSD files.
     *
     * @return string The XSD base path
     *
     * @api
     */
    public function getXsdValidationBasePath()
    {
        return false;
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return 'unteist';
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
                'notName' => [],
                'exclude' => []
            ];
        } elseif ($suite->isDir()) {
            $source = [
                'in' => $suite->getRealPath(),
                'name' => '*Test.php',
                'notName' => [],
                'exclude' => []
            ];
        } else {
            throw new \InvalidArgumentException(
                sprintf('File or directory was not found (looking for "%s")', $suite->getFilename())
            );
        }

        return $source;
    }
}
