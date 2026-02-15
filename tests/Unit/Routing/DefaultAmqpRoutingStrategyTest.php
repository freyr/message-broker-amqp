<?php

declare(strict_types=1);

namespace Freyr\MessageBroker\Amqp\Tests\Unit\Routing;

use Carbon\CarbonImmutable;
use Freyr\Identity\Id;
use Freyr\MessageBroker\Amqp\Routing\DefaultAmqpRoutingStrategy;
use Freyr\MessageBroker\Amqp\Tests\Unit\Fixtures\TestMessage;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for DefaultAmqpRoutingStrategy.
 */
final class DefaultAmqpRoutingStrategyTest extends TestCase
{
    public function testDefaultSenderName(): void
    {
        $strategy = new DefaultAmqpRoutingStrategy();
        $message = new TestMessage(id: Id::new(), name: 'Test', timestamp: CarbonImmutable::now());

        $this->assertSame('amqp', $strategy->getSenderName($message, 'test.message.sent'));
    }

    public function testCustomDefaultSenderNameViaConstructor(): void
    {
        $strategy = new DefaultAmqpRoutingStrategy(defaultSenderName: 'events');
        $message = new TestMessage(id: Id::new(), name: 'Test', timestamp: CarbonImmutable::now());

        $this->assertSame('events', $strategy->getSenderName($message, 'test.message.sent'));
    }

    public function testRoutingKeyDefaultsToMessageName(): void
    {
        $strategy = new DefaultAmqpRoutingStrategy();
        $message = new TestMessage(id: Id::new(), name: 'Test', timestamp: CarbonImmutable::now());

        $this->assertSame('order.placed', $strategy->getRoutingKey($message, 'order.placed'));
    }

    public function testHeadersContainMessageName(): void
    {
        $strategy = new DefaultAmqpRoutingStrategy();

        $headers = $strategy->getHeaders('order.placed');

        $this->assertSame([
            'x-message-name' => 'order.placed',
        ], $headers);
    }

    public function testYamlOverrideSenderName(): void
    {
        $strategy = new DefaultAmqpRoutingStrategy(routingOverrides: [
            'order.placed' => [
                'sender' => 'commerce',
            ],
        ]);
        $message = new TestMessage(id: Id::new(), name: 'Test', timestamp: CarbonImmutable::now());

        $this->assertSame('commerce', $strategy->getSenderName($message, 'order.placed'));
    }

    public function testYamlOverrideRoutingKey(): void
    {
        $strategy = new DefaultAmqpRoutingStrategy(routingOverrides: [
            'order.placed' => [
                'routing_key' => 'custom.routing.key',
            ],
        ]);
        $message = new TestMessage(id: Id::new(), name: 'Test', timestamp: CarbonImmutable::now());

        $this->assertSame('custom.routing.key', $strategy->getRoutingKey($message, 'order.placed'));
    }
}
