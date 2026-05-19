---
title: Requirements
---

# Requirements

## PHP

- PHP 8.2 or higher

## Laravel

- Laravel 10 / 11 / 12

The package targets `illuminate/support: ^10.0|^11.0|^12.0`. No compatibility shims — if you're on Laravel 9 or below, stay on `artisanpack-ui/security` 1.x.

## Dependencies

The only runtime dependency outside Laravel is `artisanpack-ui/core` (^1.0), pulled in automatically by Composer.

## Cache driver

The package caches resolved permission names and per-user permission collections. Any Laravel cache driver works, but **tagged caches** (`redis`, `memcached`) give the most precise invalidation:

| Driver | Cache invalidation strategy |
|---|---|
| `redis` / `memcached` | Tag-scoped — only RBAC entries are flushed when permissions change |
| `file` / `database` / `array` | Key-based — the package falls back to individual `forget()` calls |

If you're using `file` or `database` cache, the package still works correctly; you just lose the per-tag granularity. See [Caching](../advanced/caching.md) for tuning.

## Database

Any Eloquent-supported driver (MySQL, PostgreSQL, SQLite, SQL Server). The migrations use standard column types — no driver-specific syntax.

## Auth provider

The package reads `config('auth.providers.users.model')` to identify the User model for the `role_user` pivot. The standard Laravel auth setup is sufficient; custom guards work as long as the underlying provider exposes a user model.
