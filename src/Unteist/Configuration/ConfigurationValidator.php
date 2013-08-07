<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Configuration;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
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
        $this->configProcessesSection($rootNode);
        $this->configReportDirSection($rootNode);
        $this->configListenerSection($rootNode);
        $rootNode->append($this->getContextSection());
        $rootNode->append($this->getFiltersSection());
        $rootNode->append($this->getLoggerSection());

        return $treeBuilder;
    }

    /**
     * Get definition of processes number.
     *
     * @param ArrayNodeDefinition $rootNode
     *
     * @return ArrayNodeDefinition
     */
    private function configProcessesSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode->children()
            ->integerNode('processes')
            ->min(1)->max(10)
            ->defaultValue(1);

        return $rootNode;
    }

    /**
     * Get definition of report directory.
     *
     * @param ArrayNodeDefinition $rootNode
     *
     * @return ArrayNodeDefinition
     */
    private function configReportDirSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode->children()
            ->scalarNode('report_dir')
            ->defaultNull()
            ->cannotBeEmpty();

        return $rootNode;
    }

    /**
     * Get definition of listeners.
     *
     * @param ArrayNodeDefinition $rootNode
     *
     * @return ArrayNodeDefinition
     */
    private function configListenerSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode->children()->arrayNode('listeners')->prototype('scalar')->isRequired();

        return $rootNode;
    }

    /**
     * Get section for logger.
     *
     * @return ArrayNodeDefinition
     */
    private function getLoggerSection()
    {
        $builder = new TreeBuilder;
        $section = $builder->root('logger')->canBeEnabled();
        $definition = $section->children()->arrayNode('handlers');
        $definition->requiresAtLeastOneElement()->cannotBeEmpty()->defaultValue(['logger.handler.stream']);
        $definition->prototype('scalar');

        return $section;
    }

    /**
     * Get section for filters.
     *
     * @return ArrayNodeDefinition
     */
    private function getFiltersSection()
    {
        $builder = new TreeBuilder;
        $section = $builder->root('filters')->addDefaultsIfNotSet();

        $definition = $section->children()->arrayNode('class');
        $definition->requiresAtLeastOneElement()->cannotBeEmpty()->defaultValue(['filter.class.base']);
        $definition->prototype('scalar');

        $definition = $section->children()->arrayNode('methods');
        $definition->requiresAtLeastOneElement()->cannotBeEmpty()->defaultValue(['filter.methods.base']);
        $definition->prototype('scalar');

        return $section;
    }

    /**
     * Get section for context.
     *
     * @return ArrayNodeDefinition
     */
    private function getContextSection()
    {
        $builder = new TreeBuilder;
        $section = $builder->root('context')->addDefaultsIfNotSet();

        $definition = $section->children()->enumNode('error');
        $definition->values(['strategy.fail', 'strategy.skip', 'strategy.incomplete', 'strategy.ignore']);
        $definition->cannotBeEmpty()->defaultValue('strategy.fail');

        $definition = $section->children()->enumNode('failure');
        $definition->values(['strategy.fail', 'strategy.skip', 'strategy.incomplete', 'strategy.ignore']);
        $definition->cannotBeEmpty()->defaultValue('strategy.fail');

        $definition = $section->children()->enumNode('incomplete');
        $definition->values(['strategy.fail', 'strategy.skip', 'strategy.incomplete', 'strategy.ignore']);
        $definition->cannotBeEmpty()->defaultValue('strategy.incomplete');

        $definition = $section->children()->enumNode('skip');
        $definition->values(['strategy.fail', 'strategy.skip', 'strategy.incomplete', 'strategy.ignore']);
        $definition->cannotBeEmpty()->defaultValue('strategy.skip');

        return $section;
    }
}
