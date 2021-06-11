<?php

namespace Medelse\RefererCookieBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;


class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('medelse__referer_cookie');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode('name')->defaultValue('referer')->end()
                ->integerNode('lifetime')->defaultValue(604800)->min(0)->end()
                ->scalarNode('path')->defaultValue('/')->end()
                ->scalarNode('domain')->defaultValue('')->end()
                ->booleanNode('secure')->defaultFalse()->end()
                ->booleanNode('httponly')->defaultFalse()->end()
                ->booleanNode('auto_init')->defaultTrue()->end()
                ->booleanNode('track_internal_referer')->defaultFalse()->end()
                ->arrayNode('internal_domains')
                    ->prototype('scalar')->end()
                ->end()
                ->arrayNode('external_domains')
                    ->prototype('scalar')->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
