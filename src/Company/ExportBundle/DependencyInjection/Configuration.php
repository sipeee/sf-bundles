<?php

declare(strict_types=1);

namespace Company\ExportBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * @return TreeBuilder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('company_export');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->arrayNode('exports')
                    ->useAttributeAsKey('name', true)
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('name')
                                ->cannotBeEmpty()
                            ->end()
                            ->arrayNode('fields')
                                ->isRequired()
                                ->cannotBeEmpty()
                                ->arrayPrototype()
                                    ->children()
                                        ->scalarNode('fieldName')
                                            ->isRequired()
                                            ->cannotBeEmpty()
                                        ->end()
                                        ->scalarNode('header')
                                            ->defaultNull()
                                        ->end()
                                        ->arrayNode('transformers')
                                            ->defaultValue([])
                                            ->arrayPrototype()
                                                ->children()
                                                    ->scalarNode('class')
                                                        ->isRequired()
                                                        ->cannotBeEmpty()
                                                    ->end()
                                                    ->arrayNode('parameters')
                                                        ->defaultValue([])
                                                        ->variablePrototype()->end()
                                                    ->end()
                                                ->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
