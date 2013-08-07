<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Configuration;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Class ConfigurationValidator
 *
 * @package Unteist\Configuration
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class ConfigurationValidator implements ConfigurationInterface
{
    /**
     * Generates the configuration tree builder.
     *
     * @return TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('unteist');
        $rootNode->children()
            ->integerNode('processes')
            ->min(1)->max(10)
            ->defaultValue(1)
            ->end();

        $rootNode->children()
            ->scalarNode('report_dir')
            ->defaultNull()
            ->cannotBeEmpty()
            ->end();

        $rootNode->children()
            ->arrayNode('context')->addDefaultsIfNotSet()->children()
            // error strategy
            ->enumNode('error')->values(
                ['strategy.fail', 'strategy.skip', 'strategy.incomplete', 'strategy.ignore']
            )->cannotBeEmpty()->defaultValue('strategy.fail')->end()
            // failure strategy
            ->enumNode('failure')->values(
                ['strategy.fail', 'strategy.skip', 'strategy.incomplete', 'strategy.ignore']
            )->cannotBeEmpty()->defaultValue('strategy.fail')->end()
            // incomplete strategy
            ->enumNode('incomplete')->values(
                ['strategy.fail', 'strategy.skip', 'strategy.incomplete', 'strategy.ignore']
            )->cannotBeEmpty()->defaultValue('strategy.incomplete')->end()
            // skip strategy
            ->enumNode('skip')->values(
                ['strategy.fail', 'strategy.skip', 'strategy.incomplete', 'strategy.ignore']
            )->cannotBeEmpty()->defaultValue('strategy.skip')->end();

        $rootNode->children()
            ->arrayNode('listeners')->prototype('scalar')->isRequired()->end();

        $rootNode->children()
            ->arrayNode('logger')->canBeEnabled()->children()
            ->arrayNode('handlers')->requiresAtLeastOneElement()->defaultValue(
                ['logger.handler.stream']
            )->prototype('scalar')->end();


        return $treeBuilder;
    }
}
