---
title: Caching
---

# Caching

The package maintains two caches to keep authorization checks fast under load. Both use the application's default cache store and are tag-scoped when the store supports tags (`redis`, `memcached`).

## The two caches

### 1. Permission names cache

| Property | Value |
|---|---|
| Purpose | Fast-path the `Gate::before` hook so it only delegates to `hasPermissionTo()` for abilities that actually match a registered permission |
| Cache key | `rbac_permission_names` |
| Content | Array of all `Permission` `name` and `slug` values |
| TTL | `artisanpack.rbac.cache.permission_names_ttl` (default 3600 seconds) |
| Built by | `RbacServiceProvider::getCachedPermissionNames()` |
| Invalidated by | `PermissionObserver` on `created`, `updated`, `deleted` |

Without this cache, every `$user->can(...)` call would hit the database to check if the ability is an RBAC permission. With the cache, the check is an `in_array()` lookup against a preloaded array.

### 2. User permissions cache

| Property | Value |
|---|---|
| Purpose | Avoid repeating the recursive role-hierarchy walk on every authorization check for the same user |
| Cache key | per-user, internal to `HasPermissions` |
| Content | The user's resolved permission collection (union of direct + inherited via parent roles) |
| TTL | `artisanpack.rbac.cache.user_permissions_ttl` (default 60 seconds) |
| Built by | `HasPermissions::hasPermissionTo()` on first call |
| Invalidated by | Observer events on role / permission CRUD; manually via `$user->flushPermissionCache()` |

A short TTL (60s default) keeps the cache fresh without sacrificing the speedup for back-to-back checks within a single request.

## Tag scoping

When the cache store implements `Illuminate\Cache\TaggableStore` (i.e. `redis` or `memcached`), both caches are tagged with `artisanpack.rbac.cache.tag` (default `'rbac'`). This lets the package flush only its own entries:

```php
Cache::tags('rbac')->forget('rbac_permission_names');
Cache::tags('rbac')->flush();   // nuke every RBAC cache entry
```

For non-tagged stores (`file`, `database`, `array`), the package falls back to plain `Cache::remember()` / `Cache::forget()` calls. Functionality is identical; you just can't bulk-flush only the RBAC entries — you'd have to wait for TTL or call `Cache::flush()` (which clears everything).

## Tuning the TTLs

The defaults work well for most apps. Tune up or down based on your traffic and update frequency:

| Scenario | Suggested adjustment |
|---|---|
| Low-traffic admin app, permissions change daily | Increase both TTLs (e.g. `user_permissions_ttl` → 600, `permission_names_ttl` → 86400) |
| High-traffic public app, permissions rarely change | Keep defaults — the auto-invalidation handles freshness; the cache absorbs the hot path |
| Permissions change via direct DB mutation (no Eloquent) | Drop both TTLs to ~10 seconds so stale data clears quickly, since observers don't fire |

## Invalidation matrix

| Mutation path | Permission-names cache | User-permissions cache |
|---|---|---|
| `Permission::create / update / delete` | flushed (via observer) | flushed (via observer) |
| `Role::create / update / delete` | not flushed | flushed (via observer) |
| `$role->permissions()->attach / detach / sync` | not flushed | not flushed automatically |
| `$user->roles()->attach / detach` (raw pivot) | not flushed | not flushed |
| `$user->assignRole / removeRole` | not flushed | flushed |
| Raw `DB::table('permissions')->insert(...)` | not flushed | not flushed |

When you bypass Eloquent, call invalidation yourself:

```php
use Illuminate\Cache\TaggableStore;
use Illuminate\Support\Facades\Cache;

if ( Cache::getStore() instanceof TaggableStore ) {
    Cache::tags( config( 'artisanpack.rbac.cache.tag' ) )->flush();
} else {
    // Non-taggable store (file, database, array) — flush the known key directly.
    Cache::forget( 'rbac_permission_names' );
}

$user->flushPermissionCache();
```

`Cache::tags(...)->flush()` throws `BadMethodCallException` on non-taggable drivers (`file`, `database`, `array`). The capability check above lets the same code path work everywhere.

## Disabling the cache

There's no first-class disable flag. The simplest options:

- Set TTLs to `0` — every check effectively bypasses the cache.
- Use the `array` cache driver in CI / testing — the cache lives only for the request.

In production, leave the cache on. The `Gate::before` hook is on every authorization check; a cache miss there is expensive enough that disabling the cache hurts more than it helps.
