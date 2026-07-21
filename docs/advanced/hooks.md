---
title: Hooks
---

# Hooks

Since v1.1.0, the package integrates with [`artisanpack-ui/hooks`](https://github.com/ArtisanPack-UI/hooks) to expose lifecycle seams that let external packages graft onto the role / permission flow without subclassing traits or wiring up event listeners.

Hooks complement — they do not replace — the [Events](events.md) system. Events are best for coarse-grained observation (audit logging, notifications); hooks are best for extension seams where the package needs to compose the return value with contributions from other packages (filters) or expose a low-friction audit / policy seam that fires unconditionally on every call (actions).

## Hook reference

| Hook | Type | Fired from | Payload |
|---|---|---|---|
| `ap.rbac.abilitiesForUser` | filter | `HasPermissions::getAbilities()` | `(array $abilities, User $user)` |
| `ap.rbac.role.assigned` | action | `HasRoles::assignRole()` | `(User $user, Role $role)` |
| `ap.rbac.role.revoked` | action | `HasRoles::removeRole()` | `(User $user, Role $role)` |
| `ap.rbac.checkingAbility` | action | `Gate::before` (registered by `RbacServiceProvider`) | `(User $user, string $ability, mixed $arguments)` |

The `role.assigned` and `role.revoked` actions fire only when the pivot actually changes state — identical to their sibling `rbac.user.role_assigned` / `rbac.user.role_removed` events.

## `ap.rbac.abilitiesForUser`

The canonical extension seam. `HasPermissions::getAbilities()` collects every ability reachable through the user's role hierarchy (both `name` and `slug` for each permission), then passes the flat, de-duplicated list through this filter. Because `hasPermissionTo()`, `$user->can('slug')`, `Gate::allows('slug')`, and `@can('slug')` all resolve through `getAbilities()`, anything a filter grafts on is honored everywhere.

Typical use cases:

- Graft ability strings from an external directory (LDAP groups, Okta, Azure AD).
- Merge feature-flag-derived abilities so a flag flip immediately widens or narrows what `@can` renders.
- Overlay tenant-specific abilities in a multi-tenant app.

```php
use function addFilter;

addFilter( 'ap.rbac.abilitiesForUser', function ( array $abilities, $user ): array {
    return array_merge( $abilities, resolveLdapGroups( $user ) );
} );
```

The filter fires inside the resolved-permissions path, which sits behind the per-user permission cache. If your external source changes and you need the next `getAbilities()` call to see the new value, invalidate that cache with `$user->flushPermissionCache()`.

## `ap.rbac.role.assigned` / `ap.rbac.role.revoked`

Actions dispatched from `HasRoles::assignRole()` and `HasRoles::removeRole()` after the pivot mutation succeeds and after the sibling `rbac.user.role_assigned` / `rbac.user.role_removed` events fire.

They exist alongside the events so downstream packages that already speak the hooks vocabulary (cms-framework, security-analytics extensions) can subscribe without setting up Laravel event listeners for a single side effect.

```php
use function addAction;

addAction( 'ap.rbac.role.assigned', function ( $user, $role ): void {
    dispatch( new SyncRoleToIdp( $user, $role ) );
} );
```

Pivot mutations that bypass the trait helpers (`$user->roles()->attach($id)` etc.) do **not** fire either the events or the hooks.

## `ap.rbac.checkingAbility`

An audit seam. Every Gate check on a user carrying the `HasPermissions` trait triggers this action from the RBAC `Gate::before` shim, before the shim decides whether the ability matches an RBAC permission and before the shim falls through to standard policies. It fires unconditionally per call — matching abilities and non-matching abilities both surface here.

This is deliberate: compliance-heavy applications typically need to log every attempted authorization decision, not just the ones that ended up hitting an RBAC row.

```php
use function addAction;
use Illuminate\Support\Facades\Log;

addAction( 'ap.rbac.checkingAbility', function ( $user, string $ability, $arguments ): void {
    Log::channel( 'audit' )->info( 'rbac.check', [
        'user_id'   => $user->getKey(),
        'ability'   => $ability,
        'arguments' => $arguments,
    ] );
} );
```

Because this fires from `Gate::before`, it runs on **every** ability check on the user — including checks that end up handled by non-RBAC policies. Keep the listener cheap or defer heavy work (queued jobs, buffered log flushes) to avoid making Gate checks a hot path.

## Related packages

The sibling hooks `ap.rbac.roleRegistered` and `ap.rbac.permissionRegistered` are documented in and fired from [`artisanpack-ui/cms-framework`](https://github.com/ArtisanPack-UI/cms-framework) when it discovers and registers roles / permissions declared by packages. This package does not re-fire them.
