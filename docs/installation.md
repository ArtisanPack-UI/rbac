---
title: Installation
---

# Installation

## Install via Composer

```bash
composer require artisanpack-ui/rbac
```

The package auto-registers via Laravel's package discovery — no manual service provider entry is required.

## Run migrations

```bash
php artisan migrate
```

Four tables are created: `roles`, `permissions`, `role_user`, and `permission_role`.

## (Optional) Publish the config

```bash
php artisan vendor:publish --tag=rbac-config
```

Publishes `config/artisanpack/rbac.php`. Override model bindings, table names, foreign keys, user lookup fields, or cache settings here.

## Add the traits to your User model

```php
use ArtisanPackUI\Rbac\Concerns\HasPermissions;
use ArtisanPackUI\Rbac\Concerns\HasRoles;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasPermissions;
    use HasRoles;
}
```

Both traits are independent — you can use just `HasRoles` if you only need role assignment, but `HasPermissions` requires `HasRoles` to resolve permissions through the role hierarchy.

## Deeper topics

- [Requirements](installation/requirements.md) — PHP / Laravel versions, cache driver notes
- [Configuration](installation/configuration.md) — full config reference
