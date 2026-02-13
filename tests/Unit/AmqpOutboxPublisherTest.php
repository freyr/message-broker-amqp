<?php

declare(strict_types=1);

namespace Freyr\MessageBroker\Amqp\Tests\Unit;

use Carbon\CarbonImmutable;
use Freyr\Identity\Id;
use Freyr\MessageBroker\Amqp\AmqpOutboxPublisher;
use Freyr\MessageBroker\Amqp\Routing\DefaultAmqpRoutingStrategy;
use Freyr\MessageBroker\Amqp\Tests\Unit\Fixtures\CommerceTestMessage;
use Freyr\MessageBroker\Amqp\Tests\Unit\Fixtures\TestMessage;
use Freyr\MessageBroker\Amqp\Tests\Unit\Transport\InMemoryTransport;
use Freyr\MessageBroker\Contracts\MessageIdStamp;
use Freyr\MessageBroker\Contracts\MessageNameStamp;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use RuntimeException;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpStamp;
use Symfony\Component\Messenger\Envelope;

/**
 * Unit test for AmqpOutboxPublisher.
 */
final class AmqpOutboxPublisherTest extends TestCase
{
    private InMemoryTransport $amqpSender;

    protected function setUp(): void
    {
        $this->amqpSender = new InMemoryTransport();
    }

    public function testConventionRouting(): void
    {
        $publisher = $this->createPublisher();

        $messageId = '01234567-89ab-7def-8000-000000000001';
        $message = new TestMessage(id: Id::new(), name: 'Test', timestamp: CarbonImmutable::now());
        $envelope = new Envelope($message, [
            new MessageIdStamp(Id::fromString($messageId)),
            new MessageNameStamp('test.message.sent'),
        ]);

        $publisher->publish($envelope);

        $this->assertSame(1, $this->amqpSender->count());

        $sentEnvelope = $this->amqpSender->getLastEnvelope();
        $this->assertNotNull($sentEnvelope);

        $amqpStamp = $sentEnvelope->last(AmqpStamp::class);
        $this->assertNotNull($amqpStamp);
        $this->assertSame('test.message.sent', $amqpStamp->getRoutingKey());

        $this->assertNotNull($sentEnvelope->last(MessageIdStamp::class));
        $this->assertSame($messageId, (string) $sentEnvelope->last(MessageIdStamp::class)->messageId);
        $this->assertNotNull($sentEnvelope->last(MessageNameStamp::class));
        $this->assertSame('test.message.sent', $sentEnvelope->last(MessageNameStamp::class)->messageName);
    }

    public function testYamlOverrideRouting(): void
    {
        $publisher = $this->createPublisher(routingOverrides: [
            'test.message.sent' => [
                'sender' => 'amqp',
                'routing_key' => 'custom.override.key',
            ],
        ]);

        $message = new TestMessage(id: Id::new(), name: 'Test', timestamp: CarbonImmutable::now());
        $envelope = new Envelope($message, [
            new MessageIdStamp(Id::fromString('01234567-89ab-7def-8000-000000000001')),
            new MessageNameStamp('test.message.sent'),
        ]);

        $publisher->publish($envelope);

        $sentEnvelope = $this->amqpSender->getLastEnvelope();
        $this->assertNotNull($sentEnvelope);

        $amqpStamp = $sentEnvelope->last(AmqpStamp::class);
        $this->assertNotNull($amqpStamp);
        $this->assertSame('custom.override.key', $amqpStamp->getRoutingKey());
    }

    public function testAttributeOverrideRouting(): void
    {
        $commerceSender = new InMemoryTransport();
        $publisher = $this->createPublisher(additionalSenders: [
            'commerce' => $commerceSender,
        ]);

        $message = new CommerceTestMessage(orderId: Id::new(), amount: 99.99, placedAt: CarbonImmutable::now());
        $envelope = new Envelope($message, [
            new MessageIdStamp(Id::fromString('01234567-89ab-7def-8000-000000000001')),
            new MessageNameStamp('commerce.order.placed'),
        ]);

        $publisher->publish($envelope);

        $this->assertSame(0, $this->amqpSender->count());
        $this->assertSame(1, $commerceSender->count());

        $sentEnvelope = $commerceSender->getLastEnvelope();
        $this->assertNotNull($sentEnvelope);
        $amqpStamp = $sentEnvelope->last(AmqpStamp::class);
        $this->assertNotNull($amqpStamp);
        $this->assertSame('commerce.order.placed', $amqpStamp->getRoutingKey());
    }

    public function testThrowsWhenSenderNotInLocator(): void
    {
        $publisher = $this->createPublisher();

        $message = new CommerceTestMessage(orderId: Id::new(), amount: 50.00, placedAt: CarbonImmutable::now());
        $envelope = new Envelope($message, [
            new MessageIdStamp(Id::fromString('01234567-89ab-7def-8000-000000000001')),
            new MessageNameStamp('commerce.order.placed'),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/No AMQP sender "commerce" configured/');

        $publisher->publish($envelope);
    }

    public function testThrowsWhenMessageNameStampMissing(): void
    {
        $publisher = $this->createPublisher();

        $message = new TestMessage(id: Id::new(), name: 'Test', timestamp: CarbonImmutable::now());
        $envelope = new Envelope($message, [
            new MessageIdStamp(Id::fromString('01234567-89ab-7def-8000-000000000001')),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/missing MessageNameStamp/');

        $publisher->publish($envelope);
    }

    public function testThrowsWhenMessageIdStampMissing(): void
    {
        $publisher = $this->createPublisher();

        $message = new TestMessage(id: Id::new(), name: 'Test', timestamp: CarbonImmutable::now());
        $envelope = new Envelope($message, [new MessageNameStamp('test.message.sent')]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/missing MessageIdStamp/');

        $publisher->publish($envelope);
    }

    public function testForwardsStampsFromPublisherEnvelope(): void
    {
        $publisher = $this->createPublisher();

        $messageId = '01234567-89ab-7def-8000-000000000001';
        $message = new TestMessage(id: Id::new(), name: 'Test', timestamp: CarbonImmutable::now());
        $envelope = new Envelope($message, [
            new MessageIdStamp(Id::fromString($messageId)),
            new MessageNameStamp('test.message.sent'),
        ]);

        $publisher->publish($envelope);

        $sentEnvelope = $this->amqpSender->getLastEnvelope();
        $this->assertNotNull($sentEnvelope);

        $this->assertNotNull($sentEnvelope->last(MessageIdStamp::class));
        $this->assertNotNull($sentEnvelope->last(MessageNameStamp::class));
        $this->assertNotNull($sentEnvelope->last(AmqpStamp::class));
    }

    /**
     * @param array<string, array{sender?: string, routing_key?: string}> $routingOverrides
     * @param array<string, InMemoryTransport> $additionalSenders
     */
    private function createPublisher(
        array $routingOverrides = [],
        array $additionalSenders = [],
    ): AmqpOutboxPublisher {
        $senders = [
            'amqp' => fn () => $this->amqpSender,
        ];
        foreach ($additionalSenders as $name => $sender) {
            $senders[$name] = fn () => $sender;
        }

        return new AmqpOutboxPublisher(
            senderLocator: new ServiceLocator($senders),
            routingStrategy: new DefaultAmqpRoutingStrategy(routingOverrides: $routingOverrides),
            logger: new NullLogger(),
        );
    }
}
