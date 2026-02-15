# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## 0.2.0 - 2026-02-15

### Removed
- **[BC BREAK]** `#[AmqpExchange]` and `#[AmqpRoutingKey]` PHP attributes — routing overrides are now YAML-only via `message_broker_amqp.routing`
- Dependency on `ResolvesFromClass` trait from `freyr/message-broker-contracts`

### Changed
- `DefaultAmqpRoutingStrategy` now uses two-tier resolution (YAML overrides > convention defaults) instead of three-tier (YAML > attributes > convention)

## 0.1.1 - 2026-02-15

### Fixed
- Use Packagist for contracts dependency instead of path repository

## 0.1.0 - 2026-02-13

### Added
- Initial AMQP transport package
- `AmqpOutboxPublisher` implementing `OutboxPublisherInterface` from contracts
- Convention-based AMQP routing with `DefaultAmqpRoutingStrategy`
- Declarative topology management (exchanges, queues, bindings) via bundle configuration
- `SetupAmqpTopologyCommand` (`message-broker:setup-amqp`) with live, dry-run, and JSON dump modes
- `DefinitionsFormatter` for RabbitMQ definitions JSON export
- `AmqpConnectionFactory` for creating ext-amqp connections from DSN strings
- `AmqpArgumentNormaliser` for RabbitMQ argument type coercion
- CI pipeline with PHP 8.2/8.3/8.4 and Symfony 6.4/7.x matrix
