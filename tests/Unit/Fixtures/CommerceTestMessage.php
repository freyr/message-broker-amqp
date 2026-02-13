<?php

declare(strict_types=1);

namespace Freyr\MessageBroker\Amqp\Tests\Unit\Fixtures;

use Carbon\CarbonImmutable;
use Freyr\Identity\Id;
use Freyr\MessageBroker\Amqp\Routing\AmqpExchange;
use Freyr\MessageBroker\Contracts\MessageName;
use Freyr\MessageBroker\Contracts\OutboxMessage;

/**
 * Test message routed to a custom AMQP exchange via #[AmqpExchange].
 */
#[MessageName('commerce.order.placed')]
#[AmqpExchange('commerce')]
final readonly class CommerceTestMessage implements OutboxMessage
{
    public function __construct(
        public Id $orderId,
        public float $amount,
        public CarbonImmutable $placedAt,
    ) {}
}
