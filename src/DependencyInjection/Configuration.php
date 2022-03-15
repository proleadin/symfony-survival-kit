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
                ->arrayNode('monolog')
                    ->children()
                        ->arrayNode('handlers')
                            ->useAttributeAsKey('name')
                            ->prototype('array')
                                ->children()
                                    ->scalarNode('type')->isRequired()->end()
                                    ->scalarNode('level')->defaultValue('DEBUG')->end()
                                    ->scalarNode('path')->defaultValue('%kernel.logs_dir%/%kernel.environment%.log')->end()
                                    ->scalarNode('config')->defaultValue('%kernel.project_dir%/var/ssk/%kernel.environment%/log_handler.json')->end()
                                    ->arrayNode('publisher')
                                        ->canBeUnset()
                                        ->beforeNormalization()
                                            ->ifString()
                                            ->then(function ($v) { return ['id' => $v]; })
                                        ->end()
                                        ->children()
                                            ->scalarNode('id')->end()
                                            ->scalarNode('hostname')->end()
                                            ->scalarNode('port')->defaultValue(12201)->end()
                                            ->scalarNode('chunk_size')->defaultValue(1420)->end()
                                        ->end()
                                        ->validate()
                                            ->ifTrue(function ($v) {
                                                return !isset($v['id']) && !isset($v['hostname']);
                                            })
                                            ->thenInvalid('What must be set is either the hostname or the id.')
                                        ->end()
                                    ->end()
                                    ->arrayNode('channels')
                                        ->fixXmlConfig('channel', 'elements')
                                        ->canBeUnset()
                                        ->beforeNormalization()
                                            ->ifString()
                                            ->then(function ($v) { return ['elements' => [$v]]; })
                                        ->end()
                                        ->beforeNormalization()
                                            ->ifTrue(function ($v) { return is_array($v) && is_numeric(key($v)); })
                                            ->then(function ($v) { return ['elements' => $v]; })
                                        ->end()
                                        ->validate()
                                            ->ifTrue(function ($v) { return empty($v); })
                                            ->thenUnset()
                                        ->end()
                                        ->validate()
                                            ->always(function ($v) {
                                                $isExclusive = null;
                                                if (isset($v['type'])) {
                                                    $isExclusive = 'exclusive' === $v['type'];
                                                }

                                                $elements = [];
                                                foreach ($v['elements'] as $element) {
                                                    if (0 === strpos($element, '!')) {
                                                        if (false === $isExclusive) {
                                                            throw new InvalidConfigurationException('Cannot combine exclusive/inclusive definitions in channels list.');
                                                        }
                                                        $elements[] = substr($element, 1);
                                                        $isExclusive = true;
                                                    } else {
                                                        if (true === $isExclusive) {
                                                            throw new InvalidConfigurationException('Cannot combine exclusive/inclusive definitions in channels list');
                                                        }
                                                        $elements[] = $element;
                                                        $isExclusive = false;
                                                    }
                                                }

                                                if (!count($elements)) {
                                                    return null;
                                                }

                                                return ['type' => $isExclusive ? 'exclusive' : 'inclusive', 'elements' => $elements];
                                            })
                                        ->end()
                                        ->children()
                                            ->scalarNode('type')
                                                ->validate()
                                                    ->ifNotInArray(['inclusive', 'exclusive'])
                                                    ->thenInvalid('The type of channels has to be inclusive or exclusive')
                                                ->end()
                                            ->end()
                                            ->arrayNode('elements')
                                                ->prototype('scalar')->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                    ->scalarNode('formatter')->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}