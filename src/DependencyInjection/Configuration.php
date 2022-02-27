<?php

namespace Leadin\SurvivalKitBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('survival_kit');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->arrayNode('deployment')
                    ->children()
                        ->scalarNode('git_remote')->end()
                        ->scalarNode('git_base_branch')->end()
                        ->scalarNode('secret_token')->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
