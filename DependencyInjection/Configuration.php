<?php

namespace RevisionTen\Forms\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();

        $rootNode = $treeBuilder->root('forms');
        $rootNode
            ->children()
                ->booleanNode('load_easyadmin_config')->end()
                ->arrayNode('item_types')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('class')->end()
                            ->scalarNode('icon')->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
