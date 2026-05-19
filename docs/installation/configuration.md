---
title: Configuration
---

# Configuration

Publish the config file to override any of the defaults:

```bash
php artisan vendor:publish --tag=rbac-config
```

The full config (`config/artisanpack/rbac.php`):

```php
<?php

return [
    'models' => [
        'role'       => ArtisanPackUI\Rbac\Models\Role::class,
        'permission' => ArtisanPackUI\Rbac\Models\Permission::class,
    ],

    'tables' => [
        'role_user'       => 'role_user',
        'permission_role' => 'permission_role',
        'users'           => 'users',
    ],

    'foreign_keys' => [
        'user' => 'user_id',
    ],

    'user_lookup_fields' => ['email'],

    'cache' => [
        'user_permissions_ttl' => 60,
        'permission_names_ttl' => 3600,
        'tag'                  => 'rbac',
    ],
];
```

## `models`

Concrete classes used for the Role and Permission models. Override these when extending the base models — see [Extending the base models](../advanced/extending-models.md). Any class declared here must extend the corresponding base model so the observers, traits, and migrations continue to work.

## `tables`

Names of the pivot tables backing the role-permission and role-user relationships, plus the `users` table name. Override these only when integrating with a legacy schema; the defaults match the migrations shipped in this package.

## `foreign_keys`

Column name for the user side of the `role_user` pivot. Defaults to `user_id`.

## `user_lookup_fields`

Columns the `user:assign-role` and `user:revoke-role` Artisan commands query when the supplied identifier isn't numeric. Defaults to `['email']` so the package works on any standard Laravel users table; apps with a `username` column (or other unique handles) should append them:

```php
'user_lookup_fields' => ['email', 'username'],
```

## `cache`

Tunes the two caches the package maintains:

| Key | Default | What it caches |
|---|---|---|
| `user_permissions_ttl` | `60` (seconds) | A user's resolved permission collection (per user) |
| `permission_names_ttl` | `3600` (seconds) | The list of all known permission names / slugs for the `Gate::before` fast-path |
| `tag` | `'rbac'` | Cache tag used when a tagged cache store is available |

See [Caching](../advanced/caching.md) for invalidation rules and tuning advice.
