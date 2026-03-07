# Freyr Message Broker AMQP

Symfony bundle providing AMQP/RabbitMQ transport for the Freyr Message Broker.

## Domain Context

Implements `OutboxPublisherInterface` from `freyr/message-broker-contracts`. The publisher receives `Envelope` objects from the outbox and routes them to RabbitMQ.

Namespace: `Freyr\MessageBroker\Amqp\`

## Key Files

| File/Dir | Purpose |
|----------|--------|
| `src/AmqpOutboxPublisher.php` | Core publisher — resolves routing, attaches `AmqpStamp`, sends via Messenger sender |
| `src/Routing/` | Two-tier routing: YAML config overrides > convention defaults |
| `src/TopologyManager.php` | Declares exchanges/queues/bindings against live RabbitMQ |
| `src/DefinitionsFormatter.php` | Exports topology as RabbitMQ definitions JSON |
| `src/Command/SetupAmqpTopologyCommand.php` | CLI: `message-broker:setup-amqp` with `--dry-run`, `--dump`, live modes |
| `src/DependencyInjection/` | Bundle config key: `message_broker_amqp` (routing + topology sections) |
| `config/services.yaml` | Tags `AmqpOutboxPublisher` as `message_broker.outbox_publisher` (transport='outbox') |

## Patterns & Gotchas

- **Routing resolution:** sender defaults to `'amqp'`, routing key defaults to message name (e.g., `order.placed`). Override per-message via `message_broker_amqp.routing` YAML config.
- **Headers:** Every published message gets an `x-message-name` header automatically.
- **Binding validation:** The DI extension validates that every binding references a defined exchange and queue at compile time.
- **Argument normalisation:** `AmqpArgumentNormaliser` coerces string config values to integers for RabbitMQ arguments (`x-message-ttl`, `x-max-length`, etc.).
- **Local dev:** `compose.yaml` mounts sibling `../message-broker-contracts` for path-based development.

## Boundaries

**ASK FIRST:**
- Changes to routing strategy interface
- New topology config options
