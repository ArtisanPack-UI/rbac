---
title: Events
---

# Events

The package emits two families of events: Eloquent observer events on the models, and pivot events on `HasRoles` assignment / removal.

All event names use the `rbac.` prefix and Laravel's string-event syntax — listen for them via `Event::listen('rbac.role.created', $listener)` or in `EventServiceProvider::$listen`.

## Observer events

Dispatched by `RoleObserver` and `PermissionObserver` (attached to the configured model classes in the service provider).

| Event | Payload | Source |
|---|---|---|
| `rbac.role.created` | `Role $role` | `RoleObserver::created` |
| `rbac.role.updated` | `Role $role` | `RoleObserver::updated` |
| `rbac.role.deleted` | `Role $role` | `RoleObserver::deleted` |
| `rbac.permission.created` | `Permission $permission` | `PermissionObserver::created` |
| `rbac.permission.updated` | `Permission $permission` | `PermissionObserver::updated` |
| `rbac.permission.deleted` | `Permission $permission` | `PermissionObserver::deleted` |

## Pivot events

Laravel does not fire Eloquent events on pivot operations, so these are dispatched directly from the `HasRoles` trait.

| Event | Payload | Source |
|---|---|---|
| `rbac.user.role_assigned` | `Authenticatable $user`, `Role $role` | `HasRoles::assignRole` |
| `rbac.user.role_removed` | `Authenticatable $user`, `Role $role` | `HasRoles::removeRole` |

These fire only when the assignment / removal actually changes state (the trait is idempotent — assigning a role a user already holds is a no-op and does not fire the event).

## Listening

```php
use Illuminate\Support\Facades\Event;

Event::listen('rbac.user.role_assigned', function ($user, $role) {
    logger()->info("User {$user->id} assigned role {$role->slug}");
});

Event::listen('rbac.role.deleted', function ($role) {
    logger()->warning("Role {$role->slug} was deleted");
});
```

Or in `EventServiceProvider`:

```php
protected $listen = [
    'rbac.user.role_assigned' => [
        LogRoleAssignment::class,
    ],
    'rbac.role.deleted' => [
        AlertOnRoleDeletion::class,
    ],
];
```

## Use cases

**Audit logging** — `artisanpack-ui/security-analytics` subscribes to all six observer events and both pivot events to maintain an audit trail of authorization changes.

**Cache invalidation in downstream packages** — if you cache derived data based on roles or permissions (e.g. a precomputed permission list per organisation), subscribe to the observer events and flush your cache there.

**Notifications** — listen for `rbac.user.role_assigned` to notify the user they've been granted a new role.

**Sync to external systems** — sync role assignments to an external SSO or IdP by listening for the pivot events.

## Direct pivot mutations

If you mutate the `role_user` pivot directly (e.g. `$user->roles()->attach($id)` instead of `$user->assignRole(...)`), the pivot events do **not** fire. Always prefer `assignRole()` / `removeRole()` unless you have a specific reason to bypass them. When you do bypass them, call `$user->flushPermissionCache()` afterwards so the permission cache stays consistent.
