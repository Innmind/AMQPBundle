<?php
declare(strict_types = 1);

namespace Innmind\AMQPBundle\DependencyInjection;

use Symfony\Component\Config\Definition\{
    Builder\TreeBuilder,
    ConfigurationInterface
};

final class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder;
        $root = $treeBuilder->root('innmind_amqp');

        $root
            ->children()
                ->arrayNode('server')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('host')
                            ->isRequired()
                            ->defaultValue('localhost')
                        ->end()
                        ->integerNode('port')
                            ->isRequired()
                            ->defaultValue(5672)
                        ->end()
                        ->scalarNode('user')
                            ->isRequired()
                            ->defaultValue('guest')
                        ->end()
                        ->scalarNode('password')
                            ->isRequired()
                            ->defaultValue('guest')
                        ->end()
                        ->scalarNode('vhost')
                            ->isRequired()
                            ->defaultValue('/')
                        ->end()
                        ->integerNode('timeout')
                            ->info('Expressed in milliseconds')
                            ->defaultValue(60000)
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('exchanges')
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->children()
                            ->enumNode('type')
                                ->isRequired()
                                ->values(['direct', 'fanout', 'topic', 'headers'])
                            ->end()
                            ->booleanNode('durable')
                                ->isRequired()
                                ->defaultValue(true)
                            ->end()
                            ->arrayNode('arguments')
                                ->useAttributeAsKey('name')
                                ->prototype('variable')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('queues')
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('name')->end()
                            ->booleanNode('durable')
                                ->isRequired()
                                ->defaultValue(true)
                            ->end()
                            ->booleanNode('exclusive')
                                ->defaultValue(false)
                            ->end()
                            ->arrayNode('arguments')
                                ->useAttributeAsKey('name')
                                ->prototype('variable')->end()
                            ->end()
                            ->scalarNode('consumer')
                                ->info('Service id that will consume messages')
                                ->isRequired()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('bindings')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('exchange')
                                ->isRequired()
                            ->end()
                            ->scalarNode('queue')
                                ->isRequired()
                            ->end()
                            ->scalarNode('routingKey')
                                ->defaultValue('')
                            ->end()
                            ->arrayNode('arguments')
                                ->useAttributeAsKey('name')
                                ->prototype('variable')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('argument_translators')
                    ->prototype('scalar')
                        ->info('List of service ids implementing ArgumentTranslator')
                    ->end()
                ->end()
                ->scalarNode('clock')
                    ->info('Service id of the clock to use')
                    ->defaultValue('innmind.amqp.clock')
                ->end()
            ->end();

        return $treeBuilder;
    }
}
