---
title: Usage
---

# Usage

The package exposes its functionality through five layers — models, traits, middleware, Blade directives, and Gate integration — plus a set of Artisan commands for CRUD. Each has its own page below.

## Topics

- [Roles](usage/roles.md) — the `Role` model, hierarchy, slug helpers
- [Permissions](usage/permissions.md) — the `Permission` model, slug helpers
- [User traits (`HasRoles`, `HasPermissions`)](usage/user-traits.md)
- [Middleware (`permission:slug,slug`)](usage/middleware.md)
- [Blade directives (`@role`, `@permission`)](usage/blade-directives.md)
- [Gate integration](usage/gate-integration.md)
- [Artisan commands](usage/artisan-commands.md)

## Quick reference

```php
// Models
$editor   = Role::create(['name' => 'editor']);
$publish  = Permission::create(['name' => 'posts.publish']);
$editor->permissions()->attach($publish);

// Traits (on User)
$user->assignRole('editor');
$user->hasRole('editor');
$user->hasPermissionTo('posts.publish');

// Gate
$user->can('posts.publish');
Gate::allows('posts.publish');

// Middleware
Route::middleware('permission:posts.publish');

// Blade
@role('editor') ... @endrole
@permission('posts.publish') ... @endpermission
```

```bash
# Artisan
php artisan role:create editor
php artisan user:assign-role user@example.com editor
```
