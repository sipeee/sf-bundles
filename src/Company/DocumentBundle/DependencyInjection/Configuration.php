<?php

declare(strict_types=1);

namespace Company\DocumentBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * @return TreeBuilder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('company_document');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode('root_path')
                    ->isRequired()
                ->end()
                ->scalarNode('web_path')
                    ->isRequired()
                ->end()
                ->arrayNode('document_fields')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('entity_class')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->scalarNode('filename_field')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->scalarNode('title_field')
                                ->defaultNull()
                            ->end()
                            ->scalarNode('form_data_field')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->scalarNode('variant_field')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->scalarNode('directory_name')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->scalarNode('directory_depth')
                                ->defaultValue(10)
                            ->end()
                            ->scalarNode('url_method')
                                ->defaultNull()
                            ->end()
                            ->arrayNode('variants')
                                ->useAttributeAsKey('name', true)
                                ->arrayPrototype()
                                    ->children()
                                        ->scalarNode('name')
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
                            ->scalarNode('original_variant')
                                ->defaultValue('original')
                            ->end()
                            ->scalarNode('thumbnail_variant')
                                ->defaultValue('thumbnail')
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
