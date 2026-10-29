# EvenUpEZ — Shared Expense Management API

EvenUpEZ is a Laravel-based backend for tracking group expenses and settlements. It is part of a full-stack expense-sharing application.

## Product capabilities

- Group creation and member management
- Shared expense recording
- Equal and custom expense splits
- Balance tracking and settlement workflows
- REST API consumed by the frontend

## Engineering focus

Expense-sharing systems require consistent balance calculations, input validation, and careful handling of changes to shared expenses. This project can be discussed in interviews through its domain model, API design, authentication, and transaction integrity.

## Technology

PHP, Laravel, relational database, and REST APIs. See `composer.json` and environment configuration for the repository's exact versions and installed dependencies.

## Local setup

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Set database credentials in `.env`, then:

```bash
php artisan migrate
php artisan serve
```

Run automated tests, where configured, with:

```bash
php artisan test
```

Do not run database seeders against a non-disposable database without reviewing their effects.

## Repository map

- `app/` — application domain and HTTP code
- `routes/` — HTTP/API route definitions
- `database/migrations/` — schema changes
- `tests/` — test suite

## Verification status

This documentation describes the project's intended functionality and development setup. No performance benchmarks, passing test results, or production traffic metrics are claimed here without verification.

## Suggested technical discussion

How group balances are represented, rounding and precision for currency, handling partial settlements, transactional consistency, authorization boundaries, and integration with a frontend.
