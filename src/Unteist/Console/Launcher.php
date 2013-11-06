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
use Unteist\Configuration\Configurator;
use Unteist\Configuration\Extension;

/**
 * Class Launcher
 *
 * @package Unteist\Console
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class Launcher extends Command
{
    /**
     * @var ContainerBuilder
     */
    protected $container;

    /**
     * Configure Launcher
     */
    public function __construct()
    {
        $this->container = new ContainerBuilder();
        $this->container->set('container', $this->container);
        parent::__construct();
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
        $this->addOption(
            'parameter',
            'D',
            InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
            'Rewrite any parameter from config file or set a new one if parameter does not exists.'
        );
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
        $output->writeln(sprintf('<info>%s</info>', $this->getApplication()->getName()));
        /** @var ProgressHelper $progress */
        $progress = $this->getHelperSet()->get('progress');
        $progress->setFormat(' %percent%% [%bar%] Elapsed: %elapsed%');
        // Configurator
        $configurator = new Configurator($this->container, $input);
        $this->loadConfig($configurator);
        $this->overwriteParams($input);
        $configurator->processConfiguration();
        $configurator->configureCliReporter($progress, $output);
        $configurator->loadBootstrap();
        // Processor
        $processor = $configurator->getProcessor();
        gc_collect_cycles();
        // Run tests
        $status = $processor->run($configurator->getFiles());
        $configurator->loadCleanUp();

        return $status;
    }

    /**
     * Load all custom configuration to Configurator.
     *
     * @param Configurator $configurator
     */
    protected function loadConfig(Configurator $configurator)
    {
        $this->container->registerExtension(new Extension());
        $this->loadServicesDefinition($this->container, new FileLocator(realpath('.')), 'unteist.yml');
        $configs = $this->container->getExtensionConfig('unteist');
        foreach ($configs as $config) {
            $configurator->addConfig($config);
        }
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
     * Overwrite parameters from config file or set a new one if parameter does not exists.
     *
     * @param InputInterface $input
     */
    protected function overwriteParams(InputInterface $input)
    {
        $parameters = $input->getOption('parameter');
        $source = preg_replace_callback(
            '/(^|(?<=&))[^=[]+/',
            function ($key) {
                return urlencode(base64_encode(urldecode($key[0])));
            },
            join('&', $parameters)
        );
        parse_str($source, $parameters);
        foreach ($parameters as $key => $value) {
            if (is_array($value)) {
                array_walk_recursive(
                    $value,
                    function (&$v, &$k) {
                        $v = trim($v);
                        $k = trim($k);
                    }
                );
            } elseif (is_string($value)) {
                $value = trim($value);
            }
            $this->container->setParameter(trim(base64_decode($key)), $value);
        }
    }
}
