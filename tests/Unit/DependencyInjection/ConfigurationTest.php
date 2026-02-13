<?php

declare(strict_types=1);

namespace Freyr\MessageBroker\Amqp\Tests\Unit\DependencyInjection;

use Freyr\MessageBroker\Amqp\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;

/**
 * Unit tests for AMQP Configuration tree builder.
 */
final class ConfigurationTest extends TestCase
{
    public function testEmptyTopologyIsValid(): void
    {
        $config = $this->processConfig([]);

        $this->assertSame([], $config['topology']['exchanges']);
        $this->assertSame([], $config['topology']['queues']);
        $this->assertSame([], $config['topology']['bindings']);
    }

    public function testExchangeRequiresType(): void
    {
        $this->expectException(\Symfony\Component\Config\Definition\Exception\InvalidConfigurationException::class);

        $this->processConfig([
            'topology' => [
                'exchanges' => [
                    'commerce' => [
                        'durable' => true,
                    ],
                ],
            ],
        ]);
    }

    public function testExchangeTypeValidation(): void
    {
        $this->expectException(\Symfony\Component\Config\Definition\Exception\InvalidConfigurationException::class);

        $this->processConfig([
            'topology' => [
                'exchanges' => [
                    'commerce' => [
                        'type' => 'invalid_type',
                    ],
                ],
            ],
        ]);
    }

    public function testExchangeDefaultValues(): void
    {
        $config = $this->processConfig([
            'topology' => [
                'exchanges' => [
                    'commerce' => [
                        'type' => 'topic',
                    ],
                ],
            ],
        ]);

        $exchange = $config['topology']['exchanges']['commerce'];
        $this->assertSame('topic', $exchange['type']);
        $this->assertTrue($exchange['durable']);
        $this->assertSame([], $exchange['arguments']);
    }

    public function testAllExchangeTypesAccepted(): void
    {
        foreach (['direct', 'fanout', 'topic', 'headers'] as $type) {
            $config = $this->processConfig([
                'topology' => [
                    'exchanges' => [
                        'test' => [
                            'type' => $type,
                        ],
                    ],
                ],
            ]);

            $this->assertSame($type, $config['topology']['exchanges']['test']['type']);
        }
    }

    public function testQueueDefaultValues(): void
    {
        $config = $this->processConfig([
            'topology' => [
                'queues' => [
                    'orders_queue' => [],
                ],
            ],
        ]);

        $queue = $config['topology']['queues']['orders_queue'];
        $this->assertTrue($queue['durable']);
        $this->assertSame([], $queue['arguments']);
    }

    public function testQueueWithArguments(): void
    {
        $config = $this->processConfig([
            'topology' => [
                'queues' => [
                    'orders_queue' => [
                        'arguments' => [
                            'x-dead-letter-exchange' => 'dlx',
                            'x-queue-type' => 'quorum',
                            'x-delivery-limit' => 5,
                        ],
                    ],
                ],
            ],
        ]);

        $args = $config['topology']['queues']['orders_queue']['arguments'];
        $this->assertSame('dlx', $args['x-dead-letter-exchange']);
        $this->assertSame('quorum', $args['x-queue-type']);
        $this->assertSame(5, $args['x-delivery-limit']);
    }

    public function testBindingDefaultValues(): void
    {
        $config = $this->processConfig([
            'topology' => [
                'bindings' => [
                    [
                        'exchange' => 'commerce',
                        'queue' => 'orders_queue',
                    ],
                ],
            ],
        ]);

        $binding = $config['topology']['bindings'][0];
        $this->assertSame('commerce', $binding['exchange']);
        $this->assertSame('orders_queue', $binding['queue']);
        $this->assertSame('', $binding['binding_key']);
        $this->assertSame([], $binding['arguments']);
    }

    public function testBindingWithAllFields(): void
    {
        $config = $this->processConfig([
            'topology' => [
                'bindings' => [
                    [
                        'exchange' => 'commerce',
                        'queue' => 'orders_queue',
                        'binding_key' => 'order.*',
                        'arguments' => [
                            'x-match' => 'any',
                        ],
                    ],
                ],
            ],
        ]);

        $binding = $config['topology']['bindings'][0];
        $this->assertSame('order.*', $binding['binding_key']);
        $this->assertSame([
            'x-match' => 'any',
        ], $binding['arguments']);
    }

    public function testFullTopologyConfiguration(): void
    {
        $config = $this->processConfig([
            'topology' => [
                'exchanges' => [
                    'commerce' => [
                        'type' => 'topic',
                        'arguments' => [
                            'alternate-exchange' => 'unrouted',
                        ],
                    ],
                    'dlx' => [
                        'type' => 'direct',
                    ],
                    'unrouted' => [
                        'type' => 'fanout',
                    ],
                ],
                'queues' => [
                    'orders_queue' => [
                        'arguments' => [
                            'x-dead-letter-exchange' => 'dlx',
                            'x-queue-type' => 'quorum',
                        ],
                    ],
                    'dlq.orders' => [],
                ],
                'bindings' => [
                    [
                        'exchange' => 'commerce',
                        'queue' => 'orders_queue',
                        'binding_key' => 'order.*',
                    ],
                    [
                        'exchange' => 'dlx',
                        'queue' => 'dlq.orders',
                        'binding_key' => 'dlq.orders',
                    ],
                ],
            ],
        ]);

        $this->assertCount(3, $config['topology']['exchanges']);
        $this->assertCount(2, $config['topology']['queues']);
        $this->assertCount(2, $config['topology']['bindings']);
    }

    public function testRoutingOverrides(): void
    {
        $config = $this->processConfig([
            'routing' => [
                'order.placed' => [
                    'sender' => 'commerce',
                    'routing_key' => 'custom.key',
                ],
            ],
        ]);

        $this->assertSame('commerce', $config['routing']['order.placed']['sender']);
        $this->assertSame('custom.key', $config['routing']['order.placed']['routing_key']);
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return array{routing: array<string, array{sender: ?string, routing_key: ?string}>, topology: array{exchanges: array<string, array{type: string, durable: bool, arguments: array<string, mixed>}>, queues: array<string, array{durable: bool, arguments: array<string, mixed>}>, bindings: array<int, array{exchange: string, queue: string, binding_key: string, arguments: array<string, mixed>}>}}
     */
    private function processConfig(array $config): array
    {
        $processor = new Processor();

        /** @var array{routing: array<string, array{sender: ?string, routing_key: ?string}>, topology: array{exchanges: array<string, array{type: string, durable: bool, arguments: array<string, mixed>}>, queues: array<string, array{durable: bool, arguments: array<string, mixed>}>, bindings: array<int, array{exchange: string, queue: string, binding_key: string, arguments: array<string, mixed>}>}} */
        return $processor->processConfiguration(new Configuration(), [$config]);
    }
}
