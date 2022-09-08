<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('synolia_sylius_akeneo');
        $rootNode = $treeBuilder->getRootNode();
        $rootNode
            ->children()
                ->arrayNode('api_configuration')
                ->children()
                    ->scalarNode('base_url')
                        ->info('')
                        ->defaultValue('')
                    ->end()
                    ->scalarNode('username')
                        ->info('')
                        ->example('')
                    ->end()
                    ->scalarNode('password')
                        ->info('')
                        ->example('')
                    ->end()
                    ->scalarNode('client_id')
                        ->info('')
                        ->example('')
                    ->end()
                    ->scalarNode('client_secret')
                        ->info('')
                        ->example('')
                    ->end()
                    ->scalarNode('edition')
                        ->info('')
                        ->example('')
                    ->end()
                    ->integerNode('pagination')
                        ->info('')
                        ->defaultValue(100)
                    ->end()
                ->end()
            ->end()
        ->end();

        return $treeBuilder;
    }
}
