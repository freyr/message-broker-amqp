<?php

declare(strict_types=1);

namespace Freyr\MessageBroker\Amqp;

use Freyr\MessageBroker\Amqp\DependencyInjection\FreyrMessageBrokerAmqpExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class FreyrMessageBrokerAmqpBundle extends AbstractBundle
{
    public function getPath(): string
    {
        return dirname(__DIR__);
    }

    public function getContainerExtension(): ExtensionInterface
    {
        return new FreyrMessageBrokerAmqpExtension();
    }
}
