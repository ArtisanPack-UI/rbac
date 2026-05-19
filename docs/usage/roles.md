---
title: Roles
---

# Roles

`ArtisanPackUI\Rbac\Models\Role` is a standard Eloquent model with role-hierarchy support.

## Schema

| Field | Type | Notes |
|---|---|---|
| `name` | string | unique |
| `slug` | string | unique; auto-derived from `name` via `Str::slug()` if not provided |
| `description` | string \| null | |
| `parent_id` | int \| null | foreign key to `roles.id`; `set null` on parent delete |
| `timestamps` | datetime | |

## Relationships

- `permissions()` → `BelongsToMany Permission`
- `users()` → `BelongsToMany` against `config('auth.providers.users.model')`
- `parent()` → `BelongsTo Role`
- `children()` → `HasMany Role`

## Creating

```php
use ArtisanPackUI\Rbac\Models\Role;

$editor = Role::create([
    'name'        => 'Editor',
    // 'slug' is auto-derived to 'editor'
    'description' => 'Can publish and edit content',
]);
```

Supplying an explicit slug overrides the auto-derivation:

```php
Role::create(['name' => 'Editor', 'slug' => 'content-editor']);
```

## Hierarchy

Set `parent_id` to nest roles. Permissions resolve recursively up the chain via `HasPermissions::hasPermissionTo()`.

```php
$admin  = Role::create(['name' => 'admin']);
$editor = Role::create(['name' => 'editor', 'parent_id' => $admin->id]);

$editor->parent;    // admin Role
$admin->children;   // collection containing editor
```

A user with the `editor` role will resolve to the union of `editor`'s direct permissions plus all of `admin`'s permissions (and grandparents, recursively).

When a role is deleted, its `children` have `parent_id` set to `null` rather than being cascaded.

## Slug lookup

```php
Role::findBySlug('editor');                  // ?Role
Role::whereSlug('editor')->first();          // ?Role (via scope)
```

`hasRole('editor')` and `assignRole('editor')` (from the `HasRoles` trait) both match against either `name` or `slug`.

## Attaching permissions

```php
$editor->permissions()->attach($publishPermission);
$editor->permissions()->sync([$publish->id, $draft->id]);
$editor->permissions()->detach($draftPermission);
```

Standard Eloquent BelongsToMany — no package-specific behaviour. Sync clears the permission cache automatically via the observer (see [Caching](../advanced/caching.md)).

## Cascading deletes

`Role::delete()` detaches the role's permissions and nulls `parent_id` on its children before the row is removed. This avoids dangling pivot rows and avoids orphaned children.

## Collision detection

`Role::save()` will throw if the supplied name or slug collides with another row's value in the **opposite** column (e.g. saving `name: 'editor'` when an existing row has `slug: 'editor'`). This prevents subtle lookup ambiguity in slug-based helpers.
