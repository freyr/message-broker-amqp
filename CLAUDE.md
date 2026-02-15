# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Symfony bundle (`freyr/message-broker-amqp`) providing AMQP/RabbitMQ transport for the Freyr Message Broker. Implements the outbox publisher pattern from `freyr/message-broker-contracts`. Supports PHP 8.2+ and Symfony 6.4/7.x.

## Commands

All PHP/Composer commands must run through Docker Compose with `--rm`:

```bash
# Install dependencies
docker compose run --rm php composer install

# Run tests (all)
docker compose run --rm php vendor/bin/phpunit

# Run a single test class
docker compose run --rm php vendor/bin/phpunit tests/Unit/TopologyManagerTest.php

# Run a single test method
docker compose run --rm php vendor/bin/phpunit --filter testMethodName

# Static analysis (PHPStan level max)
docker compose run --rm php vendor/bin/phpstan analyse --memory-limit=-1

# Coding standards check
docker compose run --rm php vendor/bin/ecs check

# Coding standards fix
docker compose run --rm php vendor/bin/ecs check --fix
```

## Architecture

**Namespace:** `Freyr\MessageBroker\Amqp\` (PSR-4 from `src/`)

This bundle implements `OutboxPublisherInterface` from `freyr/message-broker-contracts`. The core flow:

1. **AmqpOutboxPublisher** — receives Messenger `Envelope` objects from the outbox, resolves routing via the routing strategy, attaches an `AmqpStamp`, and sends through Symfony Messenger's sender locator. Requires `MessageNameStamp` and `MessageIdStamp` on each envelope.

2. **Routing** (`src/Routing/`) — two-tier resolution with precedence: YAML config overrides > convention defaults.
   - `AmqpRoutingStrategyInterface` — contract for resolving sender name, routing key, and headers.
   - `DefaultAmqpRoutingStrategy` — convention-based: sender defaults to `'amqp'`, routing key defaults to the message name (e.g. `order.placed`). Per-message overrides via YAML config only (`message_broker_amqp.routing`).

3. **Topology** — declarative AMQP topology (exchanges, queues, bindings) from bundle config.
   - `TopologyManager` — declares topology against a live RabbitMQ instance via ext-amqp; also supports dry-run.
   - `DefinitionsFormatter` — exports topology as RabbitMQ definitions JSON for import via Management API.
   - `AmqpArgumentNormaliser` — coerces string config values to integers for RabbitMQ arguments (`x-message-ttl`, `x-max-length`, etc.).
   - `SetupAmqpTopologyCommand` (`message-broker:setup-amqp`) — CLI with `--dry-run`, `--dump`, and live declaration modes.

4. **DI/Config** — bundle config key is `message_broker_amqp` with `routing` (per-message overrides) and `topology` (exchanges, queues, bindings) sections. The extension validates that bindings reference defined exchanges and queues at compile time.

## Key Conventions

- All classes use `declare(strict_types=1)` and `final readonly` where possible.
- Each `use` statement imports a single class (no grouped imports).
- Coding standard: PSR-12 + Symfony + Symplify sets via ECS; Yoda conditions disabled.
- PHPStan runs at level `max`.
- The `ResolvesFromClass` trait (from contracts) provides cached PHP attribute resolution on the routing attribute classes.
- The `compose.yaml` mounts the sibling `../message-broker-contracts` directory for local development.
