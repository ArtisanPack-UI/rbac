---
title: Troubleshooting
---

# Troubleshooting

## `Call to undefined method App\Models\User::hasRole()`

The `HasRoles` trait isn't applied. Add it to your User model:

```php
use ArtisanPackUI\Rbac\Concerns\HasPermissions;
use ArtisanPackUI\Rbac\Concerns\HasRoles;

class User extends Authenticatable
{
    use HasPermissions;
    use HasRoles;
}
```

## `hasPermissionTo()` returns false even though the user has the role

Three common causes:

1. **The permission isn't attached to the role.** Check via `$role->permissions` or in the database.
2. **The user-permissions cache is stale.** This can happen if you mutated pivot tables directly (e.g. `$user->roles()->attach($id)`) without calling `assignRole()`. Run `$user->flushPermissionCache()` and try again.
3. **You're checking with the slug but stored the name (or vice versa).** Both the trait and the Gate integration check `name` AND `slug`. Verify the permission's `name` and `slug` columns in the database match what you're passing in.

## `$user->can('my.permission')` returns false but `$user->hasPermissionTo('my.permission')` returns true

The Gate `before` hook is cached. If you recently created `my.permission` and the permission-names cache hasn't picked it up yet, `Gate::before` won't recognize the ability and will fall through to standard authorization, which returns false (no policy defined).

Flush the cache manually:

```php
Cache::tags(config('artisanpack.rbac.cache.tag'))->flush();
```

Or restart your queue worker / php-fpm pool if the cache lives in-process.

The `PermissionObserver` invalidates this cache automatically on `created` / `updated` / `deleted`, so this only happens when you create permissions outside of Eloquent (e.g. raw SQL, seeders that bypass observers).

## `Class 'ArtisanPackUI\Rbac\Models\Role' not found` after publishing config

You probably published the config and then edited a `'role'` model class to a class that doesn't exist. Open `config/artisanpack/rbac.php` and verify the FQCN is correct and autoloadable. The package's defaults are:

```php
'models' => [
    'role'       => ArtisanPackUI\Rbac\Models\Role::class,
    'permission' => ArtisanPackUI\Rbac\Models\Permission::class,
],
```

## Migrations fail with "table already exists"

The migrations don't have `Schema::hasTable()` guards. If you're integrating into an app that already has `roles` or `permissions` tables (e.g. from a previous package), you'll need to either:

- Drop the existing tables before running the migration, or
- Skip the package's migrations and manage the schema yourself

## `Role name/slug collision` exception on save

The `save()` method on `Role` and `Permission` checks for name / slug collisions across columns. The exception means a row already exists where the `name` you're saving matches another row's `slug`, or vice versa. Pick distinct values.

## `permission` middleware always returns 401 even for logged-in users

The middleware uses `Auth::guest()` and `Auth::user()` — make sure the request is actually going through Laravel's auth middleware first. Apply middleware in the right order:

```php
Route::middleware(['auth', 'permission:posts.publish'])->group(function () {
    // ...
});
```

If you're using a non-default guard, set it via `Auth::shouldUse('api')` or in your route group's `auth:api` middleware before the `permission` check.

## Tests pass locally but fail in CI

Most common: the CI environment has a different cache driver. The package's behaviour is the same across drivers, but if your tests assert against specific cache keys, the tag-vs-non-tag fallback paths produce slightly different keys. Use `array` cache in tests for predictability:

```php
// phpunit.xml
<env name="CACHE_DRIVER" value="array"/>
```

## `@permission` and `@role` directives render nothing for authenticated users

Either the user doesn't actually hold the role / permission, or the User model doesn't use the `HasRoles` trait. The `@role` directive specifically guards on `method_exists($user, 'hasRole')` and silently renders nothing if the method is missing.

## Still stuck?

Open an issue at https://github.com/ArtisanPack-UI/rbac/issues with:

- Your PHP and Laravel versions
- The relevant code (User model, route definition, Blade snippet, etc.)
- The exact behaviour you're seeing vs what you expected
- The cache driver you're using
