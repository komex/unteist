<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Configuration;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\EnumNodeDefinition;
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
        $rootNode->addDefaultsIfNotSet();
        $this->configProcessesSection($rootNode);
        $this->configReportDirSection($rootNode);
        $this->configListenerSection($rootNode);
        $this->configGroupSection($rootNode);
        $rootNode->append($this->getContextSection());
        $rootNode->append($this->getFiltersSection());
        $rootNode->append($this->getLoggerSection());
        $rootNode->append($this->getSuitesSection());

        return $treeBuilder;
    }

    /**
     * Get definition of processes number.
     *
     * @param ArrayNodeDefinition $rootNode
     */
    private function configProcessesSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode->children()->integerNode('processes')->min(1)->max(10)->defaultValue(1);
    }

    /**
     * Get definition of report directory.
     *
     * @param ArrayNodeDefinition $rootNode
     */
    private function configReportDirSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode->children()->scalarNode('report_dir')->defaultNull()->cannotBeEmpty();
    }

    /**
     * Get definition of listeners.
     *
     * @param ArrayNodeDefinition $rootNode
     */
    private function configListenerSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode->children()->arrayNode('listeners')->requiresAtLeastOneElement()->prototype('scalar')->isRequired();
    }

    /**
     * Get definition of group filter.
     *
     * @param ArrayNodeDefinition $rootNode
     */
    private function configGroupSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode->children()->arrayNode('groups')->prototype('scalar')->cannotBeEmpty();
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
        $definition->requiresAtLeastOneElement()->defaultValue(['logger.handler.stream']);
        $definition->prototype('scalar')->cannotBeEmpty();

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
        $section = $builder->root('filters');
        $section->addDefaultsIfNotSet();

        $definition = $section->children()->arrayNode('class');
        $definition->requiresAtLeastOneElement()->defaultValue(['filter.class.base']);
        $definition->prototype('scalar')->cannotBeEmpty();

        $definition = $section->children()->arrayNode('methods');
        $definition->prototype('scalar')->cannotBeEmpty();

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
        $section = $builder->root('context');
        $section->addDefaultsIfNotSet();

        $definition = $section->children()->enumNode('error');
        $definition->values(['strategy.fail', 'strategy.continue']);
        $definition->cannotBeEmpty()->defaultValue('strategy.fail');

        $definition = $section->children()->enumNode('failure');
        $definition->values(['strategy.fail', 'strategy.continue']);
        $definition->cannotBeEmpty()->defaultValue('strategy.fail');

        $definition = $section->children()->enumNode('incomplete');
        $definition->values(['strategy.fail', 'strategy.incomplete', 'strategy.continue']);
        $definition->cannotBeEmpty()->defaultValue('strategy.incomplete');

        $definition = $section->children()->arrayNode('associations');
        $definition->requiresAtLeastOneElement()->useAttributeAsKey('name');
        /** @var EnumNodeDefinition $associations */
        $associations = $definition->prototype('enum');
        $associations->values(['strategy.fail', 'strategy.incomplete', 'strategy.continue']);

        $definition = $section->children()->arrayNode('levels')->requiresAtLeastOneElement();
        $definition->defaultValue(['E_ALL']);
        /** @var EnumNodeDefinition $associations */
        $associations = $definition->prototype('enum');
        $associations->values(
            ['E_ERROR', 'E_WARNING', 'E_PARSE', 'E_NOTICE', 'E_USER_ERROR', 'E_USER_WARNING', 'E_USER_NOTICE', 'E_ALL']
        );

        return $section;
    }

    /**
     * Get section for source.
     *
     * @return ArrayNodeDefinition
     */
    private function getSourceSection()
    {
        $builder = new TreeBuilder;
        $section = $builder->root('source')->requiresAtLeastOneElement();
        /** @var ArrayNodeDefinition $definition */
        $definition = $section->prototype('array');

        $definition->children()->scalarNode('in')->cannotBeEmpty()->defaultValue('.');
        $definition->children()->scalarNode('name')->cannotBeEmpty()->defaultValue('*Test.php');
        $definition->children()->scalarNode('notName')->cannotBeEmpty();

        $exclude = $definition->children()->arrayNode('exclude');
        $exclude->requiresAtLeastOneElement();
        $exclude->prototype('scalar')->cannotBeEmpty();

        return $section;
    }

    /**
     * Get section for suites.
     *
     * @return ArrayNodeDefinition
     */
    private function getSuitesSection()
    {
        $builder = new TreeBuilder;
        $section = $builder->root('suites');
        $section->requiresAtLeastOneElement();
        /** @var ArrayNodeDefinition $prototype */
        $prototype = $section->prototype('array');
        $this->configReportDirSection($prototype);
        $this->configGroupSection($prototype);
        $prototype->append($this->getContextSection());
        $prototype->append($this->getFiltersSection());
        $prototype->append($this->getSourceSection());

        return $section;
    }
}
