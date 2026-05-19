---
title: Artisan Commands
---

# Artisan Commands

Four commands for CRUD on roles and role assignments.

## `role:create`

```bash
php artisan role:create {name} [--slug=...] [--description=...]
```

Creates a new role.

| Argument / option | Required | Description |
|---|---|---|
| `name` | yes | Role name (also used to derive the slug if `--slug` is omitted) |
| `--slug` | no | Override the auto-derived slug |
| `--description` | no | Human-readable description |

Examples:

```bash
php artisan role:create Editor
php artisan role:create "Content Editor" --slug=editor
php artisan role:create Admin --description="Full administrative access"
```

## `permission:create`

```bash
php artisan permission:create {name} [--slug=...] [--description=...]
```

Creates a new permission. Same shape as `role:create`.

```bash
php artisan permission:create posts.publish
php artisan permission:create "Publish Posts" --slug=posts.publish
```

Creating a permission flushes the Gate `before` cache automatically, so the new permission is immediately usable in `$user->can(...)` checks on the next request.

## `user:assign-role`

```bash
php artisan user:assign-role {user} {role}
```

Idempotent — assigning a role to a user who already holds it is a no-op.

| Argument | Notes |
|---|---|
| `user` | User ID (numeric) **or** any value matching one of `artisanpack.rbac.config.user_lookup_fields` (default `email`) |
| `role` | Role name or slug |

Examples:

```bash
php artisan user:assign-role 42 admin
php artisan user:assign-role user@example.com editor
```

If you've added `username` to `user_lookup_fields`, you can also use:

```bash
php artisan user:assign-role jdoe editor
```

Dispatches the `rbac.user.role_assigned` event on success.

## `user:revoke-role`

```bash
php artisan user:revoke-role {user} {role}
```

Same argument shape as `user:assign-role`. Idempotent — revoking a role a user doesn't hold is a no-op. Dispatches the `rbac.user.role_removed` event on success.

```bash
php artisan user:revoke-role user@example.com editor
```

## Exit codes

All four commands return:

- `0` on success
- `1` on lookup failure (user or role not found)

The commands print human-readable messages either way.
