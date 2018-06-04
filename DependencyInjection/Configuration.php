<?php

namespace W3com\BoomBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('w3com_boom');

        $rootNode
            ->children()
            ->arrayNode('service_layer')
            ->children()
            ->scalarNode('base_uri')->end()
            ->scalarNode('path')
            ->defaultValue('/')
            ->end()
            ->scalarNode('semantic_layer_suffix')
            ->defaultValue('sml.svc/')
            ->end()
            ->booleanNode('verify_https')
            ->defaultFalse()
            ->end()
            ->integerNode('max_login_attempts')
            ->defaultValue(5)
            ->end()
            ->scalarNode('cookies_storage_path')->end()
            ->arrayNode('connections')
            ->useAttributeAsKey('name')
            ->arrayPrototype()
            ->children()
            ->scalarNode('username')->end()
            ->scalarNode('password')->end()
            ->scalarNode('database')->end()
            ->end()
            ->end()
            ->end()
            ->end()
            ->end()
            ->arrayNode('odata_service')
            ->children()
            ->scalarNode('base_uri')->end()
            ->scalarNode('path')
            ->defaultValue('/')
            ->end()
            ->booleanNode('verify_https')
            ->defaultFalse()
            ->end()
            ->arrayNode('login')
            ->children()
            ->scalarNode('username')->end()
            ->scalarNode('password')->end()
            ->end()
            ->end()
            ->end()
            ->end()
            ->scalarNode('app_namespace')
            ->defaultValue('App')
            ->end()
            ->enumNode('metadata_format')
            ->values(['annotations', 'yaml'])
            ->defaultValue('annotations')
            ->end()
            ->end();

        return $treeBuilder;
    }
}
