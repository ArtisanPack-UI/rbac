---
title: Gate Integration
---

# Gate Integration

The service provider registers a `Gate::before` hook so any ability matching a known permission slug resolves through `HasPermissions::hasPermissionTo()`. Standard Laravel authorization patterns work out of the box.

## What this gives you

```php
$user->can('posts.publish');           // true | false
$user->cannot('posts.publish');        // true | false
Gate::allows('posts.publish');         // true | false
Gate::denies('posts.publish');         // true | false
```

```blade
@can('posts.publish')
    <button>Publish</button>
@endcan
```

```php
// In a controller, request, or policy
$this->authorize('posts.publish');     // throws AuthorizationException if denied
```

All of these route through the same `Gate::before` hook and ultimately call `HasPermissions::hasPermissionTo()`.

## How the hook works

```php
Gate::before(function ($user, $ability) {
    if (! method_exists($user, 'hasPermissionTo')) {
        return null;
    }

    $permissionNames = $this->getCachedPermissionNames();

    if (in_array($ability, $permissionNames, true)) {
        return $user->hasPermissionTo($ability);
    }

    return null;
});
```

- Returning `true` or `false` from a `Gate::before` callback short-circuits the entire authorization check.
- Returning `null` defers to subsequent `Gate::before` callbacks and then to policies.
- The hook only short-circuits when the ability matches a known permission slug. Abilities like `update` or `delete-post` that don't correspond to RBAC permissions fall through to your policies untouched.

## Coexistence with policies

This is the most important property of the integration. RBAC permissions and Laravel policies do not compete — they compose.

```php
// PostPolicy
public function update(User $user, Post $post): bool
{
    return $user->id === $post->author_id;
}
```

```php
$user->can('posts.publish');       // resolves through RBAC (matches a permission slug)
$user->can('update', $post);       // resolves through PostPolicy (no matching slug)
```

You can use RBAC for the "broad capability" question ("can this user publish posts at all?") and policies for the "specific instance" question ("can this user edit *this* post?").

## Performance: the permission-names cache

`Gate::before` runs on every authorization check. Looking up every ability against the database would be expensive, so the hook keeps a cache of all known permission names + slugs and only delegates to `hasPermissionTo()` when there's a match.

The cache:

- Key: `rbac_permission_names`
- TTL: `artisanpack.rbac.cache.permission_names_ttl` (default 3600 seconds)
- Storage: tagged cache when available (`redis` / `memcached`), plain cache otherwise
- Invalidation: automatic on `Permission::created`, `Permission::updated`, `Permission::deleted` via `PermissionObserver`

See [Caching](../advanced/caching.md) for tuning.

## Edge cases

- **User model without `hasPermissionTo`**: the hook returns `null` immediately, so non-RBAC users (e.g. admin accounts on a separate model) fall through to standard authorization.
- **Empty permissions table**: the cache resolves to an empty array; every check falls through to policies.
- **Race condition during permission creation**: if a new permission is created and a Gate check fires before the cache invalidator runs, the check falls through to policies. The next request picks up the fresh cache. This window is microseconds in normal apps; if it matters for your use case, invalidate manually after the create — conditionally on the cache driver:

  ```php
  if ( Cache::getStore() instanceof Illuminate\Cache\TaggableStore ) {
      Cache::tags( config( 'artisanpack.rbac.cache.tag' ) )->flush();
  } else {
      Cache::forget( 'rbac_permission_names' );
  }
  ```
