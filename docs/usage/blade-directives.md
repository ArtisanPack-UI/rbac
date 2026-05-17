---
title: Blade Directives
---

# Blade Directives

The service provider registers two pairs of directives for view-layer authorization.

## `@role`

Renders the inner block only if the authenticated user holds the named role.

```blade
@role('admin')
    <a href="/admin">Admin dashboard</a>
@endrole
```

- Argument: role name **or** slug (matched against both).
- Unauthenticated users: directive renders nothing.
- Matching: checks `auth()->user()->hasRole(...)` if the user has the `HasRoles` trait; safely renders nothing if the User model doesn't implement `hasRole()`.

## `@permission`

Renders the inner block only if the authenticated user holds the named permission, including permissions inherited through the role hierarchy.

```blade
@permission('posts.publish')
    <button>Publish</button>
@endpermission
```

- Argument: permission name **or** slug.
- Unauthenticated users: directive renders nothing.
- Resolution: routes through `$user->can(...)`, which dispatches to the Gate integration and ultimately `HasPermissions::hasPermissionTo()`.

## `@permission` vs `@can`

`@can` is Laravel's built-in directive. Because the package wires permissions through `Gate::before`, `@can('posts.publish')` and `@permission('posts.publish')` resolve to the same result.

```blade
@can('posts.publish')
    <button>Publish</button>
@endcan
```

Use whichever reads better in context. `@permission` makes the intent ("this is an RBAC permission") explicit; `@can` is consistent with the rest of your Laravel codebase. Both are fine.

## Combining with policies

`@can` (and the underlying `Gate::allows`) checks the RBAC permission first via `Gate::before`. If the ability doesn't match a known permission slug, control falls through to your defined policies. This means:

```blade
@can('update', $post)
    <button>Edit</button>
@endcan
```

…runs your `PostPolicy::update($user, $post)` even when the RBAC package is installed, because `update` isn't a registered permission slug. RBAC permissions and per-resource policies coexist cleanly.

## Reference: directive expansions

The directives expand to inline PHP guards:

```php
// @role('admin')
<?php if(auth()->check() && method_exists(auth()->user(), 'hasRole') && auth()->user()->hasRole('admin')): ?>

// @permission('posts.publish')
<?php if(auth()->check() && auth()->user()->can('posts.publish')): ?>
```

…and the closers expand to `<?php endif; ?>`.
