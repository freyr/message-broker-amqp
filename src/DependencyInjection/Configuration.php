<?php

declare(strict_types=1);

namespace Freyr\MessageBroker\Amqp\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('message_broker_amqp');

        $treeBuilder->getRootNode()
            ->children()

            // Routing overrides
            ->arrayNode('routing')
            ->info('Override convention-based routing for specific message names')
            ->useAttributeAsKey('message_name')
            ->defaultValue([])
            ->arrayPrototype()
            ->children()
            ->scalarNode('sender')
            ->info('Override sender/transport name (default: amqp)')
            ->defaultNull()
            ->end()
            ->scalarNode('routing_key')
            ->info('Override AMQP routing key (default: full message name)')
            ->defaultNull()
            ->end()
            ->end()
            ->end()
            ->end()

            // Topology
            ->arrayNode('topology')
            ->addDefaultsIfNotSet()
            ->children()

            // Exchanges
            ->arrayNode('exchanges')
            ->info('AMQP exchanges to declare (keyed by name)')
            ->useAttributeAsKey('name')
            ->defaultValue([])
            ->arrayPrototype()
            ->children()
            ->enumNode('type')
            ->info('Exchange type: direct, fanout, topic, or headers')
            ->values(['direct', 'fanout', 'topic', 'headers'])
            ->isRequired()
            ->end()
            ->booleanNode('durable')
            ->info('Whether the exchange survives broker restart')
            ->defaultTrue()
            ->end()
            ->arrayNode('arguments')
            ->normalizeKeys(false)
            ->info('Optional exchange arguments (e.g., alternate-exchange)')
            ->defaultValue([])
            ->variablePrototype()
            ->end()
            ->end()
            ->end()
            ->end()
            ->end()

            // Queues
            ->arrayNode('queues')
            ->info('AMQP queues to declare (keyed by name)')
            ->useAttributeAsKey('name')
            ->defaultValue([])
            ->arrayPrototype()
            ->children()
            ->booleanNode('durable')
            ->info('Whether the queue survives broker restart')
            ->defaultTrue()
            ->end()
            ->arrayNode('arguments')
            ->normalizeKeys(false)
            ->info('Optional queue arguments (e.g., x-dead-letter-exchange, x-queue-type)')
            ->defaultValue([])
            ->variablePrototype()
            ->end()
            ->end()
            ->end()
            ->end()
            ->end()

            // Bindings
            ->arrayNode('bindings')
            ->info('Queue-to-exchange bindings')
            ->defaultValue([])
            ->arrayPrototype()
            ->children()
            ->scalarNode('exchange')
            ->info('Source exchange name')
            ->isRequired()
            ->cannotBeEmpty()
            ->end()
            ->scalarNode('queue')
            ->info('Destination queue name')
            ->isRequired()
            ->cannotBeEmpty()
            ->end()
            ->scalarNode('binding_key')
            ->info('Binding key pattern (e.g., "order.*")')
            ->defaultValue('')
            ->end()
            ->arrayNode('arguments')
            ->normalizeKeys(false)
            ->info('Optional binding arguments')
            ->defaultValue([])
            ->variablePrototype()
            ->end()
            ->end()
            ->end()
            ->end()
            ->end()

            ->end()
            ->end()

            ->end();

        return $treeBuilder;
    }
}
