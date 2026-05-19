---
title: FAQ
---

# FAQ

## Does this package depend on `artisanpack-ui/security`?

No. `rbac` is standalone — it pulls in only `artisanpack-ui/core` and standard Laravel packages. It's safe to install in any Laravel app or package, including ones that don't use the rest of the ArtisanPack UI security ecosystem.

## Can a user have more than one role?

Yes. `HasRoles::assignRole()` doesn't restrict the count — you can stack as many roles as you want on a user. Permissions resolve as the union across all of the user's roles (and their ancestors).

## Can a role inherit from more than one parent?

No. The `parent_id` column is a single foreign key — each role has at most one parent. If you need multiple inheritance, you'll need to give the user multiple direct roles instead.

## How deep can the role hierarchy go?

There's no hard limit, but every level adds one more database query when resolving permissions (unless the user-permissions cache is warm). For most apps, 3–4 levels is plenty; if you find yourself going deeper, consider whether some of those levels could be replaced with multiple direct role assignments.

## How is this different from `spatie/laravel-permission`?

- This package supports role hierarchy (`parent_id`); Spatie's doesn't.
- This package is intentionally smaller — no team / guard scoping, no JSON column for permissions, no Bouncer-style abilities.
- This package's models are subclassable via config without a `morphMap`-style indirection.
- This package emits dedicated `rbac.*` events that downstream packages (notably `artisanpack-ui/security-analytics`) subscribe to for auditing.

If you need teams or guards, Spatie's is the better fit. If you need role hierarchy or you're already in the ArtisanPack UI ecosystem, use this one.

## Can I use this with multi-tenancy?

Yes, but the package doesn't ship a tenant-aware scope. The simplest pattern: subclass `Role` and `Permission`, add a `tenant_id` column via your own migration, and override the `roles()` and `permissions()` relationships to apply the tenant scope. Bind your subclasses via config.

## Does `assignRole()` work transactionally?

It uses Eloquent's `BelongsToMany::attach()` under the hood, which performs a single insert. If you wrap a batch of role assignments in `DB::transaction()`, the whole batch is atomic.

## Why does `findBySlug` exist when I have `whereSlug`?

`findBySlug('editor')` is shorthand for `Role::whereSlug('editor')->first()`. Same result, less code at the call site. Pick whichever reads better for the context.

## What happens if I delete a user with assigned roles?

Standard Eloquent behaviour applies — the `role_user` pivot rows orphan unless you've set up the foreign key with `onDelete('cascade')`. The package's migrations don't cascade on the user side because the package can't assume control of the users table; add cascading yourself if you want it.

## Can two roles have the same name?

No. `name` is unique on the `roles` table, and `Role::save()` actively checks for collision with existing `slug` values too (and vice versa). This prevents lookup ambiguity for the slug-based helpers.

## How do I bulk-assign a permission to many roles?

```php
$roleIds = Role::whereIn('slug', ['editor', 'admin', 'publisher'])->pluck('id');
$permission->roles()->attach($roleIds);
```

Or sync to set the exact list:

```php
$permission->roles()->sync($roleIds);
```

Both go through standard Eloquent's `BelongsToMany`. Pivot `attach()` / `detach()` / `sync()` operations do **not** fire model events on the `Permission` or `Role`, so the permission-names cache and user-permissions cache are not invalidated automatically. If you mutate role-permission relationships directly via the pivot, call `$user->flushPermissionCache()` for each affected user (or rely on the per-request cache miss to pick up the new state).

## Where do the `rbac.*` events come from?

See [Events](advanced/events.md). They're standard Laravel string events dispatched from `RoleObserver`, `PermissionObserver`, and the `HasRoles` trait. Listen for them via `Event::listen()` or `EventServiceProvider::$listen`.
