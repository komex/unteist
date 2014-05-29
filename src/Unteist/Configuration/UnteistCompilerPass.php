<?php
/**
 * This file is a part of unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Configuration;


use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Finder\Finder;

/**
 * Class UnteistCompilerPass
 *
 * @package Unteist\Configuration
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class UnteistCompilerPass implements CompilerPassInterface
{
    /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @param ContainerBuilder $container
     *
     * @api
     */
    public function process(ContainerBuilder $container)
    {
        $this->configureListeners($container);
        $this->configureLogger($container);
        $this->configureGroups($container);
        $this->configureContext($container);
        $this->configureProcessor($container);
        $this->configureRunner($container);
        $this->findTests($container);
    }

    /**
     * @param ContainerBuilder $container
     */
    private function findTests(ContainerBuilder $container)
    {
        $files = new \ArrayObject();
        foreach ($container->getParameter('sources') as $source) {
            $finder = new Finder();
            $finder->ignoreUnreadableDirs()->files();
            $finder->in($source['in'])->name($source['name']);
            foreach ($source['notName'] as $name) {
                $finder->notName($name);
            }
            $finder->exclude($source['exclude']);
            /** @var \SplFileInfo $file */
            foreach ($finder as $file) {
                $realPath = $file->getRealPath();
                if (!$files->offsetExists($realPath) && substr($file->getFilename(), -8) === 'Test.php') {
                    $files[$realPath] = $file;
                }
            }
        }
        $container->setParameter('suites', $files);
    }

    /**
     * @param ContainerBuilder $container
     */
    private function configureRunner(ContainerBuilder $container)
    {
        $filters = $container->getParameter('filters');
        $definition = $container->getDefinition('runner');
        foreach ($filters['methods'] as $id) {
            $definition->addMethodCall('addMethodsFilter', [new Reference($id)]);
        }
    }

    /**
     * @param ContainerBuilder $container
     */
    private function configureProcessor(ContainerBuilder $container)
    {
        $context = $container->getParameter('context');
        $definition = $container->getDefinition('processor.single');
        $definition->addMethodCall('setErrorHandler', [$context['levels']]);
        $filters = $container->getParameter('filters');
        foreach ($filters['class'] as $id) {
            $definition->addMethodCall('addClassFilter', [new Reference($id)]);
        }
        if (intval($container->getParameter('processes')) > 1) {
            $definition = $container->getDefinition('processor.multi');
        }
        // Bootstrap
        $bootstrap = $container->getParameter('bootstrap');
        if ($bootstrap !== null) {
            $definition->setFile($bootstrap);
        }
        // Alias
        $container->setDefinition('processor', $definition);
    }

    /**
     * @param ContainerBuilder $container
     */
    private function configureGroups(ContainerBuilder $container)
    {
        $groups = $container->getParameter('groups');
        if (!empty($groups)) {
            $definition = $container->getDefinition('filter.methods.group');
            $definition->setArguments([$container->getParameter('groups')]);
            $filters = $container->getParameter('filters');
            array_unshift($filters['methods'], 'filter.methods.group');
            $container->setParameter('filters', $filters);
        }
    }

    /**
     * @param ContainerBuilder $container
     */
    private function configureListeners(ContainerBuilder $container)
    {
        $taggedServices = $container->findTaggedServiceIds('listener');
        $definition = $container->getDefinition('dispatcher');
        foreach ($taggedServices as $id => $attributes) {
            $definition->addMethodCall('addSubscriber', [new Reference($id)]);
        }
    }

    /**
     * @param ContainerBuilder $container
     */
    private function configureLogger(ContainerBuilder $container)
    {
        $definition = $container->getDefinition('logger');
        foreach ($container->getParameter('logger.handlers') as $handler) {
            $definition->addMethodCall('pushHandler', [new Reference($handler)]);
        }
    }

    /**
     * @param ContainerBuilder $container
     */
    private function configureContext(ContainerBuilder $container)
    {
        $context = $container->getParameter('context');
        $definition = $container->getDefinition('context');
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
    }
}
