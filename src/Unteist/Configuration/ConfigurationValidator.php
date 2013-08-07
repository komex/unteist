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
            ->defaultValue(1);

        $rootNode->children()
            ->scalarNode('report_dir')
            ->defaultNull()
            ->cannotBeEmpty();

        $rootNode->append($this->getContextSection());

        $rootNode->children()
            ->arrayNode('listeners')->prototype('scalar')->isRequired();

        $rootNode->children()
            ->arrayNode('logger')->canBeEnabled()->children()
            ->arrayNode('handlers')->requiresAtLeastOneElement()->defaultValue(
                ['logger.handler.stream']
            )->prototype('scalar');

        $rootNode->append($this->getFiltersSection());


        return $treeBuilder;
    }

    /**
     * Get section for filters.
     *
     * @return \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition
     */
    private function getFiltersSection()
    {
        $builder = new TreeBuilder;
        $filters = $builder->root('filters');
        $node = $filters->addDefaultsIfNotSet()->children();
        $node->arrayNode('class')->requiresAtLeastOneElement()->cannotBeEmpty()->defaultValue(
            ['filter.class.base']
        )->prototype('scalar');
        $node->arrayNode('methods')->requiresAtLeastOneElement()->cannotBeEmpty()->defaultValue(
            ['filter.methods.base']
        )->prototype('scalar');

        return $filters;
    }

    /**
     * Get section for context.
     *
     * @return \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition
     */
    private function getContextSection()
    {
        $builder = new TreeBuilder;
        $context = $builder->root('context');
        $node = $context->addDefaultsIfNotSet()->children();
        $node->enumNode('error')->values(
            ['strategy.fail', 'strategy.skip', 'strategy.incomplete', 'strategy.ignore']
        )->cannotBeEmpty()->defaultValue('strategy.fail');
        $node->enumNode('failure')->values(
            ['strategy.fail', 'strategy.skip', 'strategy.incomplete', 'strategy.ignore']
        )->cannotBeEmpty()->defaultValue('strategy.fail');
        $node->enumNode('incomplete')->values(
            ['strategy.fail', 'strategy.skip', 'strategy.incomplete', 'strategy.ignore']
        )->cannotBeEmpty()->defaultValue('strategy.incomplete');
        $node->enumNode('skip')->values(
            ['strategy.fail', 'strategy.skip', 'strategy.incomplete', 'strategy.ignore']
        )->cannotBeEmpty()->defaultValue('strategy.skip');

        return $context;
    }
}
