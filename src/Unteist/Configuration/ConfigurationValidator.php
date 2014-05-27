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
        $tree = new TreeBuilder();
        $root = $tree->root('unteist');
        $this->configProcessesSection($root)->defaultValue(1);
        $this->configGroupSection($root);
        $this->configLoggerSection($root)->defaultValue(['logger.handler.null']);
        $this->configIncludeFile($root, 'bootstrap');
        $root->append($this->getContextSection()->addDefaultsIfNotSet());
        $root->append($this->getFiltersSection()->addDefaultsIfNotSet());
        $root->append($this->getSuitesSection());
        $root->append($this->getSourceSection()->addDefaultChildrenIfNoneSet());

        return $tree;
    }

    /**
     * Get definition of processes number.
     *
     * @param ArrayNodeDefinition $rootNode
     *
     * @return \Symfony\Component\Config\Definition\Builder\NumericNodeDefinition
     */
    private function configProcessesSection(ArrayNodeDefinition $rootNode)
    {
        return $rootNode->children()->integerNode('processes')->min(1)->max(10);
    }

    /**
     * Get definition of listeners.
     *
     * @param ArrayNodeDefinition $rootNode
     * @param string $name
     */
    private function configIncludeFile(ArrayNodeDefinition $rootNode, $name)
    {
        $rootNode->children()->scalarNode($name)->cannotBeEmpty();
    }

    /**
     * Get definition of group filter.
     *
     * @param ArrayNodeDefinition $rootNode
     */
    private function configGroupSection(ArrayNodeDefinition $rootNode)
    {
        $groups = $rootNode->children()->arrayNode('groups')->requiresAtLeastOneElement();
        $groups->prototype('scalar')->cannotBeEmpty();
    }

    /**
     * @param ArrayNodeDefinition $rootNode
     *
     * @return \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition
     */
    private function configLoggerSection(ArrayNodeDefinition $rootNode)
    {
        $logger = $rootNode->children()->arrayNode('logger')->requiresAtLeastOneElement();
        $logger->prototype('scalar')->cannotBeEmpty();

        return $logger;
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

        $definition = $section->children()->enumNode('error');
        $definition->values(['strategy.fail', 'strategy.continue']);
        $definition->cannotBeEmpty()->defaultValue('strategy.fail');

        $definition = $section->children()->enumNode('failure');
        $definition->values(['strategy.exception', 'strategy.continue']);
        $definition->cannotBeEmpty()->defaultValue('strategy.continue');

        $definition = $section->children()->enumNode('incomplete');
        $definition->values(['strategy.exception', 'strategy.continue']);
        $definition->cannotBeEmpty()->defaultValue('strategy.continue');

        $definition = $section->children()->enumNode('beforeCase');
        $definition->values(['strategy.exception', 'strategy.continue']);
        $definition->cannotBeEmpty()->defaultValue('strategy.continue');

        $definition = $section->children()->enumNode('beforeTest');
        $definition->values(['strategy.exception', 'strategy.continue']);
        $definition->cannotBeEmpty()->defaultValue('strategy.continue');

        $definition = $section->children()->enumNode('afterTest');
        $definition->values(['strategy.exception', 'strategy.continue']);
        $definition->cannotBeEmpty()->defaultValue('strategy.exception');

        $definition = $section->children()->enumNode('afterCase');
        $definition->values(['strategy.exception', 'strategy.continue']);
        $definition->cannotBeEmpty()->defaultValue('strategy.exception');

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

        $definition->children()->scalarNode('in')->cannotBeEmpty()->defaultValue('./tests');
        $definition->children()->scalarNode('name')->cannotBeEmpty()->defaultValue('*Test.php');

        $exclude = $definition->children()->arrayNode('notName');
        $exclude->requiresAtLeastOneElement();
        $exclude->prototype('scalar')->cannotBeEmpty();

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
        $this->configProcessesSection($prototype);
        $this->configGroupSection($prototype);
        $this->configLoggerSection($prototype);
        $this->configIncludeFile($prototype, 'bootstrap');
        $prototype->append($this->getContextSection());
        $prototype->append($this->getFiltersSection());
        $prototype->append($this->getSourceSection());
        $prototype->validate()->always(
            function (array $list) {
                foreach (['listeners', 'groups', 'logger', 'source'] as $key) {
                    if (empty($list[$key])) {
                        unset($list[$key]);
                    }
                }

                return $list;
            }
        );

        return $section;
    }
}
