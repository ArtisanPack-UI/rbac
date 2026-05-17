---
title: Extending the Base Models
---

# Extending the Base Models

The `Role` and `Permission` model classes are bound via config, so you can substitute your own subclasses without forking the package. Useful when a downstream package or application needs to add relationships, scopes, or behaviour to the base models.

The `cms-framework` package uses this pattern for its Users module — its `Role` subclass adds a `policies()` relationship without forking RBAC.

## 1. Subclass the base model

```php
namespace App\Models;

use ArtisanPackUI\Rbac\Models\Role as BaseRole;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends BaseRole
{
    protected $table = 'roles';

    public function policies(): HasMany
    {
        return $this->hasMany(Policy::class);
    }
}
```

Critical: **extend the base model**. Don't reimplement it. The traits, observers, migrations, and Artisan commands all rely on the base methods + casts.

## 2. Register your subclass

```php
// config/artisanpack/rbac.php
return [
    'models' => [
        'role'       => App\Models\Role::class,
        'permission' => ArtisanPackUI\Rbac\Models\Permission::class,
    ],

    // ... rest of config
];
```

## 3. That's it

Everything that touches the model — Artisan commands, observers, `HasRoles::roles()`, the Gate integration, the Blade directives — reads from the config and uses your subclass. You can keep adding methods, scopes, and relationships on `App\Models\Role` indefinitely.

## What you can't change this way

The base columns (`name`, `slug`, `description`, `parent_id` for `Role`) are baked into the shipped migration. If you need different columns, ship your own migration that alters the tables after the package's migration runs.

The pivot tables (`role_user`, `permission_role`) are configurable by name (see [`tables`](../installation/configuration.md#tables)) but their column shape isn't. If you need additional pivot columns, override `getPivotColumns()` on your subclass and ship a migration that adds them.

## Subclassing both Role and Permission

```php
'models' => [
    'role'       => App\Models\Role::class,
    'permission' => App\Models\Permission::class,
],
```

Same pattern. The base classes are independent — subclassing one doesn't require subclassing the other.
