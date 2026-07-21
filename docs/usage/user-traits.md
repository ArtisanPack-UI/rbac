---
title: User Traits
---

# User Traits

Two traits live in `ArtisanPackUI\Rbac\Concerns`. Apply both to your User model.

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

## `HasRoles`

Adds the `roles()` relationship plus assignment helpers.

```php
$user->roles;                           // Collection<Role>
$user->hasRole('admin');                // string, Role, or Collection<Role|string>
$user->hasRole($adminRole);
$user->hasRole(collect(['admin', 'editor']));  // true if user holds ANY listed role

$user->assignRole('admin');             // idempotent; no-op for unknown role slugs
$user->assignRole($adminRole);
$user->removeRole('admin');
```

### Events

`assignRole()` and `removeRole()` dispatch:

- `rbac.user.role_assigned` (user, role)
- `rbac.user.role_removed` (user, role)

These are dispatched directly from the trait because Laravel does not fire Eloquent events for pivot operations. See [Events](../advanced/events.md) for the full event reference.

### Idempotency

`assignRole('admin')` on a user who already has the `admin` role is a no-op (no duplicate pivot row, no extra event). Same for `removeRole()` against a role the user doesn't hold.

If `assignRole()` receives a role name / slug that doesn't exist, it silently no-ops rather than throwing. This is intentional for the Artisan commands' UX — pass the unresolved role through your own validation if you need stricter handling.

## `HasPermissions`

Resolves the user's effective permission set by walking the role hierarchy. Requires `HasRoles` to be present on the same model.

```php
$user->hasPermissionTo('posts.publish');   // bool
$user->hasPermission('posts.publish');     // alias of hasPermissionTo
$user->getAbilities();                     // array<int, string> — flat ability list
$user->flushPermissionCache();             // call after manual pivot mutations
```

### Resolution algorithm

1. Load the user's direct roles via `HasRoles::roles()`.
2. For each role, walk up the `parent` chain collecting permissions at each level.
3. Union the resulting permission collection and cache it under a per-user cache key for `artisanpack.rbac.cache.user_permissions_ttl` seconds (default 60).
4. `getAbilities()` flattens the collection into a de-duplicated array of ability strings (each permission contributes both its `name` and its `slug`) and runs it through the `ap.rbac.abilitiesForUser` filter so external sources can graft additional abilities onto the user.
5. `hasPermissionTo()` returns `true` if the supplied string appears anywhere in `getAbilities()`.

See [Hooks](../advanced/hooks.md) for the filter contract and grafting patterns.

### Manual cache invalidation

The cache is invalidated automatically when:

- A `Role` or `Permission` row is created / updated / deleted (via observers).

It is **not** invalidated when you mutate pivot tables directly (e.g. `$user->roles()->attach($roleId)` without going through `assignRole`). In those cases, call `$user->flushPermissionCache()` yourself.

## Combining the traits

`HasRoles` works standalone if you only need role assignment without permission resolution. But `HasPermissions` depends on `HasRoles` to know which roles to resolve permissions through, so it should always be paired.

Order in the `use` block doesn't matter — they don't share method names.
