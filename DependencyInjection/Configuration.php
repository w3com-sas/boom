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
        $treeBuilder = new TreeBuilder('w3com_boom');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
            ->arrayNode('service_layer')
            ->children()
            ->scalarNode('semantic_layer_suffix')
            ->defaultValue('sml.svc/')
            ->end()
            ->integerNode('language')
            ->defaultValue(22)
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
            ->scalarNode('uri')->end()
            ->scalarNode('path')
            ->defaultValue('/')
            ->end()
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
            ->booleanNode('verify_https')
            ->defaultFalse()
            ->end()
            ->arrayNode('connections')
            ->useAttributeAsKey('name')
            ->arrayPrototype()
            ->children()
            ->scalarNode('uri')->end()
            ->scalarNode('path')
            ->defaultValue('/')
            ->end()
            ->scalarNode('username')->end()
            ->scalarNode('password')->end()
            ->end()
            ->end()
            ->end()
            ->end()
            ->end()
            ->scalarNode('app_namespace')
            ->defaultValue('App')
            ->end()
            ->scalarNode('entity_directory')
            ->defaultValue('%kernel.project_dir%/src/AppBundle/HanaEntity')
            ->end()
            ->enumNode('metadata_format')
            ->values(['annotations', 'yaml'])
            ->defaultValue('annotations')
            ->end()
            ->end();

        return $treeBuilder;
    }
}
