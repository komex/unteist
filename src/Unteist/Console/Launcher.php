<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Console;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Unteist\Configuration\ConfigurationProcessor;
use Unteist\Configuration\UnteistCompilerPass;
use Unteist\Processor\Processor;

/**
 * Class Launcher
 *
 * @package Unteist\Console
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class Launcher extends Command
{
    /**
     * Configure command.
     */
    protected function configure()
    {
        $this->setName('run')->setDescription('Run tests stored in specified directory.');
        $this->addArgument(
            'suite',
            InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
            'Suite name in config file or path to TestCase classes.'
        );
        $this->addOption('processes', 'p', InputOption::VALUE_REQUIRED, 'Run test in N separated processes.');
        $this->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'Unteist config file.', 'unteist.yml');
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
        $container = new ContainerBuilder();
        $container->set('cli.output', $output);
        $output->writeln(sprintf('<info>%s</info>', $this->getApplication()->getName()));
        // Configurator
        $configurator = new ConfigurationProcessor($input->getArgument('suite'));
        $configurator->setProcesses($input->getOption('processes'));
        $configurator->setGroups($input->getOption('group'));
        $container->addCompilerPass(new UnteistCompilerPass());
        $container->registerExtension($configurator);
        $container->loadFromExtension($configurator->getAlias());
        $directory = realpath(join(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..']));
        $loader = new YamlFileLoader($container, new FileLocator($directory));
        $loader->load('services.yml');
        $config = new \SplFileInfo($input->getOption('config'));
        if ($config->isReadable() and $config->isFile()) {
            $loader = new YamlFileLoader($container, new FileLocator());
            $loader->load($config->getRealPath());
        }
        $container->compile();
        $this->overwriteParameters($container, $input->getOption('parameter'));
        /** @var Processor $processor */
        $processor = $container->get('processor');
        gc_collect_cycles();

        // Run tests and return status code.
        return $processor->run();
    }

    /**
     * @param ContainerBuilder $container
     * @param array $parameters
     */
    private function overwriteParameters(ContainerBuilder $container, array $parameters)
    {
        if (empty($parameters)) {
            return;
        }
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
            $container->setParameter(trim(base64_decode($key)), $value);
        }
    }
}
