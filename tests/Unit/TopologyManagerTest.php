<?php

declare(strict_types=1);

namespace Freyr\MessageBroker\Amqp\Tests\Unit;

use Freyr\MessageBroker\Amqp\TopologyManager;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for TopologyManager.
 */
final class TopologyManagerTest extends TestCase
{
    public function testDryRunWithIndependentExchanges(): void
    {
        $topology = $this->createTopology(
            exchanges: [
                'alpha' => [
                    'type' => 'topic',
                    'durable' => true,
                    'arguments' => [],
                ],
                'beta' => [
                    'type' => 'direct',
                    'durable' => true,
                    'arguments' => [],
                ],
            ],
        );

        $manager = new TopologyManager($topology);
        $actions = $manager->dryRun();

        $this->assertCount(2, $actions);
        $combined = implode("\n", $actions);
        $this->assertStringContainsString('"alpha"', $combined);
        $this->assertStringContainsString('"beta"', $combined);
    }

    public function testDryRunWithFullTopology(): void
    {
        $topology = $this->createTopology(
            exchanges: [
                'commerce' => [
                    'type' => 'topic',
                    'durable' => true,
                    'arguments' => [],
                ],
                'dlx' => [
                    'type' => 'direct',
                    'durable' => true,
                    'arguments' => [],
                ],
            ],
            queues: [
                'orders_queue' => [
                    'durable' => true,
                    'arguments' => [
                        'x-dead-letter-exchange' => 'dlx',
                    ],
                ],
            ],
            bindings: [
                [
                    'exchange' => 'commerce',
                    'queue' => 'orders_queue',
                    'binding_key' => 'order.*',
                    'arguments' => [],
                ],
            ],
        );

        $manager = new TopologyManager($topology);
        $actions = $manager->dryRun();

        $this->assertCount(4, $actions);

        $this->assertStringContainsString('exchange "commerce"', $actions[0]);
        $this->assertStringContainsString('topic', $actions[0]);

        $queueAction = $actions[2];
        $this->assertStringContainsString('queue "orders_queue"', $queueAction);

        $bindingAction = $actions[3];
        $this->assertStringContainsString('Bind queue "orders_queue"', $bindingAction);
        $this->assertStringContainsString('exchange "commerce"', $bindingAction);
        $this->assertStringContainsString('"order.*"', $bindingAction);
    }

    public function testDryRunWithEmptyTopology(): void
    {
        $topology = $this->createTopology();
        $manager = new TopologyManager($topology);

        $this->assertSame([], $manager->dryRun());
    }

    public function testDryRunShowsEmptyBindingKey(): void
    {
        $topology = $this->createTopology(
            exchanges: [
                'events' => [
                    'type' => 'fanout',
                    'durable' => true,
                    'arguments' => [],
                ],
            ],
            queues: [
                'all_events' => [
                    'durable' => true,
                    'arguments' => [],
                ],
            ],
            bindings: [
                [
                    'exchange' => 'events',
                    'queue' => 'all_events',
                    'binding_key' => '',
                    'arguments' => [],
                ],
            ],
        );

        $manager = new TopologyManager($topology);
        $actions = $manager->dryRun();

        $bindingAction = $actions[2];
        $this->assertStringContainsString('(empty)', $bindingAction);
    }

    /**
     * @param array<string, array{type: string, durable: bool, arguments: array<string, mixed>}> $exchanges
     * @param array<string, array{durable: bool, arguments: array<string, mixed>}> $queues
     * @param array<int, array{exchange: string, queue: string, binding_key: string, arguments: array<string, mixed>}> $bindings
     *
     * @return array{exchanges: array<string, array{type: string, durable: bool, arguments: array<string, mixed>}>, queues: array<string, array{durable: bool, arguments: array<string, mixed>}>, bindings: array<int, array{exchange: string, queue: string, binding_key: string, arguments: array<string, mixed>}>}
     */
    private function createTopology(array $exchanges = [], array $queues = [], array $bindings = []): array
    {
        return [
            'exchanges' => $exchanges,
            'queues' => $queues,
            'bindings' => $bindings,
        ];
    }
}
