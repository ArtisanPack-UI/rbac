# ArtisanPack UI — RBAC

Role-based access control for Laravel: roles with hierarchy, permissions, Blade directives, middleware, and Gate integration.

This package is **standalone** — it does not depend on `artisanpack-ui/security` and can be used by any Laravel application or package (e.g. `cms-framework`) that needs roles and permissions without pulling in the full security suite.

## Features

- Eloquent `Role` and `Permission` models with parent/child role hierarchy
- `HasRoles` and `HasPermissions` traits for the `User` model
- Recursive permission resolution through the role hierarchy, with per-user caching
- Route middleware (`permission:slug,slug`) for permission-gated routes
- `@role` and `@permission` Blade directives
- Gate integration (`$user->can('slug')`, `Gate::allows('slug')`, `@can('slug')`) backed by RBAC permissions
- Artisan commands for CRUD over roles and role assignments
- Configurable model bindings so consumers can extend `Role` / `Permission` with their own subclasses
- Eloquent observer events on the `rbac.*` channel for downstream auditing

## Installation

```bash
composer require artisanpack-ui/rbac
```

Run the package migrations:

```bash
php artisan migrate
```

(Optional) Publish the config file to override model bindings, table names, or cache settings:

```bash
php artisan vendor:publish --tag=rbac-config
```

## Quick start

Add the traits to your `User` model:

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

Create a role + permission and assign them:

```php
use ArtisanPackUI\Rbac\Models\Permission;
use ArtisanPackUI\Rbac\Models\Role;

$editor = Role::create(['name' => 'editor']);
$publish = Permission::create(['name' => 'posts.publish']);

$editor->permissions()->attach($publish);
$user->assignRole('editor');

$user->hasRole('editor');             // true
$user->hasPermissionTo('posts.publish'); // true
$user->can('posts.publish');             // true (via Gate integration)
```

## Models

### Role

`ArtisanPackUI\Rbac\Models\Role`

| Field | Type | Notes |
|---|---|---|
| `name` | string | unique |
| `slug` | string | unique; auto-derived from `name` via `Str::slug()` if not provided |
| `description` | string\|null | |
| `parent_id` | int\|null | foreign key to `roles.id`; `set null` on parent delete |

Relationships:

- `permissions()` → `BelongsToMany Permission`
- `users()` → `BelongsToMany` against `auth.providers.users.model`
- `parent()` → `BelongsTo Role`
- `children()` → `HasMany Role`

Slug helpers: `Role::findBySlug('editor')`, `Role::whereSlug('editor')->first()`. `hasRole('editor')` and `assignRole('editor')` match against either `name` or `slug`.

Deleting a role detaches its permissions and nulls `parent_id` on its children.

### Permission

`ArtisanPackUI\Rbac\Models\Permission`

| Field | Type | Notes |
|---|---|---|
| `name` | string | unique |
| `slug` | string | unique; auto-derived from `name` via `Str::slug()` if not provided |
| `description` | string\|null | |

Relationships:

- `roles()` → `BelongsToMany Role`

Slug helpers: `Permission::findBySlug('pages.publish')`, `Permission::whereSlug('pages.publish')->first()`. `hasPermissionTo('pages.publish')` and the Gate integration both match against either `name` or `slug`.

Deleting a permission detaches it from all roles.

## Traits

### `HasRoles`

Adds the `roles()` relationship plus assignment helpers.

```php
$user->roles;                          // collection of Role
$user->hasRole('admin');               // by name, Role instance, or Collection
$user->assignRole('admin');            // idempotent; no-op for unknown role names
$user->removeRole('admin');
```

`assignRole` and `removeRole` dispatch the `rbac.user.role_assigned` and `rbac.user.role_removed` events with the user and role.

### `HasPermissions`

Resolves the user's effective permission set by walking the role hierarchy. Pair with `HasRoles`.

```php
$user->hasPermissionTo('posts.publish');
$user->hasPermission('posts.publish');     // alias of hasPermissionTo
$user->flushPermissionCache();             // call after manual relationship mutations
```

The trait caches the resolved permission collection per user. The cache TTL is configurable via `artisanpack.rbac.cache.user_permissions_ttl`.

## Middleware

The service provider aliases `permission` to `ArtisanPackUI\Rbac\Http\Middleware\CheckPermission`. Apply it to routes that require one or more permissions — the user must hold at least one to proceed.

```php
Route::get('/posts/{post}/publish', PublishController::class)
    ->middleware('permission:posts.publish');

Route::get('/admin', AdminController::class)
    ->middleware('permission:admin.dashboard,admin.metrics');
```

Unauthenticated requests abort with `401`, authorized requests return `200`, and authenticated requests without any of the listed permissions abort with `403`.

## Blade directives

```blade
@role('admin')
    <a href="/admin">Admin dashboard</a>
@endrole

@permission('posts.publish')
    <button>Publish</button>
@endpermission
```

Both directives render nothing for unauthenticated users.

`@permission` resolves through the user's effective permission set, which means it composes with role hierarchy. Use Laravel's built-in `@can` directive when you want the same check to flow through the Gate integration:

```blade
@can('posts.publish')
    <button>Publish</button>
@endcan
```

## Gate integration

The service provider registers a `Gate::before` hook that resolves any ability matching a known permission slug through `hasPermissionTo()`. Standard Laravel authorization patterns work out of the box:

```php
$user->can('posts.publish');           // true|false
Gate::allows('posts.publish');         // true|false
Gate::denies('posts.publish');         // true|false
```

Abilities that don't correspond to an RBAC permission slug fall through to the normal Gate/Policy lookup, so policies you've defined elsewhere continue to work.

The list of known permission slugs is cached for `artisanpack.rbac.cache.permission_names_ttl` seconds. The cache is invalidated automatically when permissions are created, updated, or deleted.

## Artisan commands

```bash
php artisan role:create {name} [--slug=...] [--description=...]
php artisan permission:create {name} [--slug=...] [--description=...]
php artisan user:assign-role {user} {role}
php artisan user:revoke-role {user} {role}
```

`{user}` accepts a numeric ID or any value that matches one of the configured `user_lookup_fields` (defaults to `email`). The assign/revoke commands are idempotent.

## Configuration reference

Published to `config/artisanpack/rbac.php`.

```php
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

## Extending the base models

Other packages can extend `Role` and `Permission` and rebind them through config — useful when a downstream package needs extra relationships or scopes without forking this package. The `cms-framework` package uses this pattern for its Users module.

```php
namespace App\Models;

use ArtisanPackUI\Rbac\Models\Role as BaseRole;

class Role extends BaseRole
{
    protected $table = 'roles';

    public function policies(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Policy::class);
    }
}
```

```php
// config/artisanpack/rbac.php
'models' => [
    'role'       => App\Models\Role::class,
    'permission' => ArtisanPackUI\Rbac\Models\Permission::class,
],
```

The Artisan commands, observers, traits, and migrations all read this config, so the substituted class is used everywhere.

## Events

The service provider attaches observers that dispatch:

- `rbac.role.created`, `rbac.role.updated`, `rbac.role.deleted`
- `rbac.permission.created`, `rbac.permission.updated`, `rbac.permission.deleted`

Pivot mutations are dispatched directly from the trait helpers since Laravel does not fire pivot events:

- `rbac.user.role_assigned`, `rbac.user.role_removed`

Listen for these in any application or sibling package — `security-analytics` uses them for audit logging.

## Testing

```bash
composer test
```

Tests run against an in-memory SQLite database via Orchestra Testbench. The package's own test suite covers role / permission models, traits, middleware, Artisan commands, Blade directives, Gate integration, observers, and the configurable model-binding pattern.

## Contributing

As an open source project, this package is open to contributions from anyone. Please [read through the contributing guidelines](CONTRIBUTING.md) to learn more about how you can contribute.
