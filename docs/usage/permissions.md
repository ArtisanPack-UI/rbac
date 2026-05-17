---
title: Permissions
---

# Permissions

`ArtisanPackUI\Rbac\Models\Permission` is a standard Eloquent model representing a single, granular capability.

## Schema

| Field | Type | Notes |
|---|---|---|
| `name` | string | unique |
| `slug` | string | unique; auto-derived from `name` via `Str::slug()` if not provided |
| `description` | string \| null | |
| `timestamps` | datetime | |

## Relationships

- `roles()` → `BelongsToMany Role`

## Creating

```php
use ArtisanPackUI\Rbac\Models\Permission;

$publish = Permission::create([
    'name'        => 'posts.publish',
    'description' => 'Publish blog posts',
]);
```

The auto-slug runs `Str::slug()` on the name. `'posts.publish'` becomes `'postspublish'` — if you want dot-separated slugs preserved, set `slug` explicitly:

```php
Permission::create(['name' => 'posts.publish', 'slug' => 'posts.publish']);
```

## Naming conventions

Two common conventions both work well:

- **Dotted resource notation** — `posts.publish`, `users.delete`, `admin.dashboard`
- **Action-first** — `publish-posts`, `delete-users`, `view-admin-dashboard`

Use whichever you prefer; the package treats permission names as opaque strings. Whatever you pick, supply an explicit `slug` to keep the lookup key clean.

## Slug lookup

```php
Permission::findBySlug('posts.publish');
Permission::whereSlug('posts.publish')->first();
```

`hasPermissionTo('posts.publish')` and the Gate integration both match against either `name` or `slug`.

## Attaching to roles

Permissions are attached to roles (not directly to users). To grant a user a permission, attach it to a role they hold:

```php
$editor->permissions()->attach($publish);
```

A user can then check the permission via:

```php
$user->hasPermissionTo('posts.publish');
$user->can('posts.publish');
```

…both of which recursively resolve through the role hierarchy.

## Cascading deletes

`Permission::delete()` detaches the permission from all roles before the row is removed.

## Collision detection

`Permission::save()` will throw if the supplied name or slug collides with another row's value in the **opposite** column. Same protection as `Role::save()`.

## Cache invalidation

Creating, updating, or deleting a permission flushes the `rbac_permission_names` cache via `PermissionObserver`, so the Gate `before` fast-path picks up the change on the next request. See [Caching](../advanced/caching.md) for details.
