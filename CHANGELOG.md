# ArtisanPack UI RBAC Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.1] - 2026-06-14

### Added

- Laravel 13 support. The `illuminate/support` constraint now accepts `^10.0|^11.0|^12.0|^13.0`, and the test toolchain (`orchestra/testbench`, `pestphp/pest`, `pestphp/pest-plugin-laravel`) was widened so the Laravel 13 leg installs cleanly.

### Changed

- CI now runs the test suite as a matrix across Laravel 12 and 13 × PHP 8.2-8.4 (Laravel 13 / PHP 8.2 excluded — Laravel 13 requires PHP 8.3+). CI also triggers on `release/**` branches and overrides `composer config platform.php` per matrix row so the Laravel 13 leg resolves correctly despite the repo's PHP 8.2 platform pin.

## [1.0.0] - 2026-05-18

### Added

- Initial release of the standalone RBAC package, extracted from `artisanpack-ui/security` 1.x as part of the Security 2.0 package split.
- `Role` Eloquent model with parent / child hierarchy, name + auto-derived slug, optional description, and collision-detecting `save()`.
- `Permission` Eloquent model with name + auto-derived slug and collision-detecting `save()`.
- `HasRoles` user trait: `roles()` relationship, `hasRole()`, `assignRole()`, `removeRole()` helpers; idempotent assignment and removal.
- `HasPermissions` user trait: `hasPermissionTo()` (recursive resolution through the role hierarchy), `hasPermission()` alias, `flushPermissionCache()` manual invalidation.
- `permission` route middleware alias (`permission:posts.publish,posts.review`) — accepts one or more permission slugs and aborts 401 / 403 as appropriate.
- `@role` and `@permission` Blade directives for view-layer permission gating.
- Gate integration via `Gate::before` so `$user->can('slug')`, `Gate::allows('slug')`, and `@can('slug')` resolve through RBAC permissions while still falling through to standard policies for non-RBAC abilities.
- Artisan commands: `role:create`, `permission:create`, `user:assign-role`, `user:revoke-role` — idempotent and configurable user lookup fields.
- Eloquent observers dispatching `rbac.role.{created,updated,deleted}` and `rbac.permission.{created,updated,deleted}` events for downstream auditing.
- Pivot events `rbac.user.role_assigned` and `rbac.user.role_removed` dispatched directly from the `HasRoles` trait.
- Migrations for `roles`, `permissions`, `role_user`, and `permission_role` tables.
- Configurable model bindings (`artisanpack.rbac.models.role/permission`) so downstream packages can extend the base models without forking.
- Configurable table names, foreign keys, and user lookup fields for legacy schema integration.
- Permission-name cache backed by Laravel's tagged cache where available, with automatic invalidation on permission CRUD; user-permission cache with configurable TTL.
- `Rbac` Facade and `rbac()` helper as the public entry point for future API expansion.

### Changed

- (none — initial release)

### Removed

- This package contains the role / permission / Blade directive / Gate integration content previously bundled in `artisanpack-ui/security` 1.x. See the [`artisanpack-ui/security` UPGRADE guide](https://github.com/ArtisanPack-UI/security/blob/main/UPGRADE.md) for migration instructions from 1.x.
