---
title: Middleware
---

# Middleware

The service provider aliases `permission` to `ArtisanPackUI\Rbac\Http\Middleware\CheckPermission`. Apply it to any route that requires one or more permissions.

## Single permission

```php
Route::get('/posts/{post}/publish', PublishController::class)
    ->middleware('permission:posts.publish');
```

## Multiple permissions (OR)

Comma-separate permissions to require **any** of them:

```php
Route::get('/admin', AdminController::class)
    ->middleware('permission:admin.dashboard,admin.metrics');
```

The user only needs one of `admin.dashboard` or `admin.metrics` to proceed.

## Behaviour

| State | HTTP response |
|---|---|
| Unauthenticated (guest) | `401 Unauthorized` |
| Authenticated, has one of the listed permissions | passes to controller (`200` or whatever the controller returns) |
| Authenticated, has none of the listed permissions | `403 Forbidden` |

Both abort codes use Laravel's default `abort()` helper, so your application's normal 401 / 403 error views render as expected.

## Implementation reference

```php
class CheckPermission
{
    public function handle(Request $request, Closure $next, string ...$permissions)
    {
        if (Auth::guest()) {
            abort(401);
        }

        $user = Auth::user();

        foreach ($permissions as $permission) {
            if ($user->can($permission)) {
                return $next($request);
            }
        }

        abort(403);
    }
}
```

The middleware delegates to `$user->can()`, which routes through the Gate integration and ultimately calls `HasPermissions::hasPermissionTo()`. So permissions inherited via the role hierarchy are honoured here.

## When to use this vs other patterns

- **Use the middleware** for route-level gating where a missing permission should produce an HTTP 403.
- **Use `@permission` Blade directives** for view-level gating where a missing permission should hide UI elements.
- **Use `$user->can()` / `Gate::allows()`** in controllers when the response depends on the permission outcome (e.g. show different fields, branch behaviour).
- **Use Policies** when authorization logic involves the resource (e.g. "user can edit this post only if they authored it"). The Gate integration composes cleanly with policies — RBAC handles broad capabilities, policies handle per-resource rules.

## "AND" semantics

The middleware is OR-by-design. If you need AND semantics (user must hold every listed permission), apply the middleware multiple times:

```php
Route::middleware(['permission:admin.dashboard', 'permission:admin.metrics']);
```

Or implement a custom middleware that calls `$user->hasPermissionTo()` for each requirement.
