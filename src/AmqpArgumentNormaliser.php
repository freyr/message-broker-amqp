<?php

declare(strict_types=1);

namespace Freyr\MessageBroker\Amqp;

/**
 * Normalise AMQP arguments to ensure correct types for RabbitMQ.
 *
 * RabbitMQ requires certain queue/exchange arguments to be integers.
 * Symfony config often provides them as strings. This utility handles
 * the type coercion in one place.
 */
final class AmqpArgumentNormaliser
{
    /**
     * Queue arguments that must be integers for RabbitMQ.
     */
    private const INTEGER_ARGUMENTS = [
        'x-message-ttl',
        'x-max-length',
        'x-max-length-bytes',
        'x-max-priority',
        'x-expires',
        'x-delivery-limit',
    ];

    /**
     * @param array<string, mixed> $arguments
     *
     * @return array<string, mixed>
     */
    public static function normalise(array $arguments): array
    {
        foreach (self::INTEGER_ARGUMENTS as $key) {
            if (isset($arguments[$key]) && is_numeric($arguments[$key])) {
                $arguments[$key] = (int) $arguments[$key];
            }
        }

        return $arguments;
    }
}
