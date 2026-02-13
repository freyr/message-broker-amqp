<?php

declare(strict_types=1);

namespace Freyr\MessageBroker\Amqp\Tests\Unit\Transport;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\TransportInterface;

/**
 * In-memory transport for unit testing.
 */
final class InMemoryTransport implements TransportInterface
{
    /** @var array<Envelope> */
    private array $sent = [];

    /** @var array<Envelope> */
    private array $acknowledged = [];

    /** @var array<Envelope> */
    private array $rejected = [];

    public function send(Envelope $envelope): Envelope
    {
        $this->sent[] = $envelope;

        return $envelope;
    }

    public function get(): iterable
    {
        if (empty($this->sent)) {
            return [];
        }

        $unacknowledged = array_filter(
            $this->sent,
            fn (Envelope $e) => !in_array($e, $this->acknowledged, true) && !in_array($e, $this->rejected, true)
        );

        return array_values($unacknowledged);
    }

    public function ack(Envelope $envelope): void
    {
        $this->acknowledged[] = $envelope;
    }

    public function reject(Envelope $envelope): void
    {
        $this->rejected[] = $envelope;
    }

    /**
     * @return array<Envelope>
     */
    public function getSentEnvelopes(): array
    {
        return $this->sent;
    }

    public function getLastEnvelope(): ?Envelope
    {
        if (empty($this->sent)) {
            return null;
        }

        return $this->sent[array_key_last($this->sent)];
    }

    public function clear(): void
    {
        $this->sent = [];
        $this->acknowledged = [];
        $this->rejected = [];
    }

    public function count(): int
    {
        return count($this->sent);
    }
}
