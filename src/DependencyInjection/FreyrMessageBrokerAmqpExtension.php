<?php

declare(strict_types=1);

namespace Freyr\MessageBroker\Amqp\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

final class FreyrMessageBrokerAmqpExtension extends Extension
{
    /**
     * @param array<mixed> $configs
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        /** @var array{routing: array<string, array{sender: ?string, routing_key: ?string}>, topology: array{exchanges: array<string, mixed>, queues: array<string, mixed>, bindings: array<int, array{exchange: string, queue: string, binding_key: string, arguments: array<string, mixed>}>}} $config */
        $this->validateBindingReferences($config['topology']);

        $container->setParameter('message_broker_amqp.routing_overrides', $config['routing']);
        $container->setParameter('message_broker_amqp.topology', $config['topology']);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../../config'));
        $loader->load('services.yaml');
    }

    public function getAlias(): string
    {
        return 'message_broker_amqp';
    }

    /**
     * Validate that bindings reference exchanges and queues defined in the topology.
     *
     * @param array{exchanges: array<string, mixed>, queues: array<string, mixed>, bindings: array<int, array{exchange: string, queue: string, binding_key: string, arguments: array<string, mixed>}>} $topology
     */
    private function validateBindingReferences(array $topology): void
    {
        foreach ($topology['bindings'] as $index => $binding) {
            if (!isset($topology['exchanges'][$binding['exchange']])) {
                throw new \InvalidArgumentException(sprintf(
                    'Binding #%d references undefined exchange "%s".',
                    $index + 1,
                    $binding['exchange'],
                ));
            }

            if (!isset($topology['queues'][$binding['queue']])) {
                throw new \InvalidArgumentException(sprintf(
                    'Binding #%d references undefined queue "%s".',
                    $index + 1,
                    $binding['queue'],
                ));
            }
        }
    }
}
