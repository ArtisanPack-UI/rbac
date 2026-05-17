---
title: Getting Started
---

# Getting Started

A five-minute path from install to a working role / permission check.

## 1. Install

```bash
composer require artisanpack-ui/rbac
php artisan migrate
```

## 2. Add the traits to your User model

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

## 3. Create a role and a permission

```php
use ArtisanPackUI\Rbac\Models\Permission;
use ArtisanPackUI\Rbac\Models\Role;

$editor = Role::create(['name' => 'editor']);
$publish = Permission::create(['name' => 'posts.publish']);

$editor->permissions()->attach($publish);
```

Or use Artisan:

```bash
php artisan role:create editor
php artisan permission:create posts.publish
```

## 4. Assign the role to a user

```php
$user->assignRole('editor');
```

Or:

```bash
php artisan user:assign-role user@example.com editor
```

## 5. Check authorization

```php
$user->hasRole('editor');                // true
$user->hasPermissionTo('posts.publish'); // true
$user->can('posts.publish');             // true — via Gate integration
```

In a route:

```php
Route::get('/posts/{post}/publish', PublishController::class)
    ->middleware('permission:posts.publish');
```

In a Blade view:

```blade
@permission('posts.publish')
    <button>Publish</button>
@endpermission

@role('editor')
    <a href="/editor">Editor dashboard</a>
@endrole
```

## Next steps

- [Usage](usage.md) — full reference for the traits, middleware, directives, and Gate integration.
- [Advanced](advanced.md) — extending the base models, listening for events, tuning the cache.
- [Installation](installation.md) — requirements, configuration, and integration with existing schemas.
