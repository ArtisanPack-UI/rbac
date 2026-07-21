---
title: ArtisanPack UI RBAC Documentation
---

# ArtisanPack UI RBAC

Role-based access control for Laravel: roles with hierarchy, permissions, Blade directives, middleware, and Gate integration.

This package is **standalone** — it does not depend on `artisanpack-ui/security` and can be used by any Laravel application or package (e.g. `cms-framework`) that needs roles and permissions without pulling in the full security suite.

## What's in this package

- Eloquent `Role` and `Permission` models with parent / child role hierarchy
- `HasRoles` and `HasPermissions` traits for the User model
- Recursive permission resolution through the role hierarchy, with per-user caching
- Route middleware (`permission:slug,slug`) for permission-gated routes
- `@role` and `@permission` Blade directives
- Gate integration (`$user->can('slug')`, `Gate::allows('slug')`, `@can('slug')`) backed by RBAC permissions
- Artisan commands for CRUD over roles and role assignments
- Configurable model bindings so consumers can extend `Role` / `Permission` with their own subclasses
- Eloquent observer events on the `rbac.*` channel for downstream auditing
- `artisanpack-ui/hooks` lifecycle seams (`ap.rbac.*`) for grafting abilities from external sources and auditing Gate checks

## Documentation map

- [Getting Started](getting-started.md) — 5-minute install + first role / permission
- [Installation](installation.md) — install, requirements, configuration
- [Usage](usage.md) — roles, permissions, middleware, Blade directives, Gate integration, Artisan commands
- [Advanced](advanced.md) — extending the base models, events, caching
- [FAQ](faq.md)
- [Troubleshooting](troubleshooting.md)

## Related packages

| Package | Scope |
|---|---|
| [`artisanpack-ui/security`](https://github.com/ArtisanPack-UI/security) | Core: input sanitization, output escaping, KSES, CSP, security headers |
| [`artisanpack-ui/security-auth`](https://github.com/ArtisanPack-UI/security-auth) | 2FA, password complexity, account lockout, sessions |
| [`artisanpack-ui/security-advanced-auth`](https://github.com/ArtisanPack-UI/security-advanced-auth) | WebAuthn, SSO, social login |
| [`artisanpack-ui/secure-uploads`](https://github.com/ArtisanPack-UI/secure-uploads) | File validation, malware scanning, secure storage |
| [`artisanpack-ui/security-analytics`](https://github.com/ArtisanPack-UI/security-analytics) | Event logging, anomaly detection, SIEM, dashboards (subscribes to `rbac.*` events) |
