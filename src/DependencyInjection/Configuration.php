<?php

namespace Leadin\SurvivalKitBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('survival_kit');
        $treeBuilder->root('survival_kit');
        return $treeBuilder;
    }
}
