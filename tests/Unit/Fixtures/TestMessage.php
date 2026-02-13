<?php

declare(strict_types=1);

namespace Freyr\MessageBroker\Amqp\Tests\Unit\Fixtures;

use Carbon\CarbonImmutable;
use Freyr\Identity\Id;
use Freyr\MessageBroker\Contracts\MessageName;
use Freyr\MessageBroker\Contracts\OutboxMessage;

/**
 * Test message for unit testing.
 */
#[MessageName('test.message.sent')]
final readonly class TestMessage implements OutboxMessage
{
    public function __construct(
        public Id $id,
        public string $name,
        public CarbonImmutable $timestamp,
    ) {}
}
