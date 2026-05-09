<?php

declare(strict_types=1);

namespace ArtisanPackUI\Rbac\Concerns;

use ArtisanPackUI\Rbac\Models\Role;
use Illuminate\Cache\TaggableStore;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Add permission resolution to an authenticatable model.
 *
 * Walks the role hierarchy to compute the effective permission set, caching
 * the result per-user. Pair with {@see HasRoles} so the model exposes a
 * `roles()` relationship and `parent`/`children` chain to walk.
 */
trait HasPermissions
{
    public function hasPermissionTo(string $permission): bool
    {
        $permissions = $this->resolvePermissions();

        return $permissions->contains('name', $permission)
            || $permissions->contains('slug', $permission);
    }

    /**
     * Backwards-compatible alias retained for callers that still use the
     * pre-extraction method name.
     */
    public function hasPermission(string $permission): bool
    {
        return $this->hasPermissionTo($permission);
    }

    public function flushPermissionCache(): void
    {
        $cacheKey = $this->permissionCacheKey();

        if ($this->cacheSupportsTagging()) {
            Cache::tags([config('artisanpack.rbac.cache.tag', 'rbac')])->forget($cacheKey);

            return;
        }

        Cache::forget($cacheKey);
    }

    protected function resolvePermissions(): Collection
    {
        $cacheKey = $this->permissionCacheKey();
        $ttl = (int) config('artisanpack.rbac.cache.user_permissions_ttl', 60);
        $loader = fn () => $this->loadAllPermissions();

        if ($this->cacheSupportsTagging()) {
            return Cache::tags([config('artisanpack.rbac.cache.tag', 'rbac')])
                ->remember($cacheKey, $ttl, $loader);
        }

        return Cache::remember($cacheKey, $ttl, $loader);
    }

    protected function loadAllPermissions(): Collection
    {
        $all = collect();

        $this->roles->each(function ($role) use (&$all): void {
            $all = $all->merge($this->collectPermissionsForRole($role));
        });

        return $all;
    }

    /**
     * @param  array<int, int>  $visited
     */
    protected function collectPermissionsForRole(Role $role, array $visited = []): Collection
    {
        if (in_array($role->getKey(), $visited, true)) {
            return collect();
        }

        $visited[] = $role->getKey();
        $permissions = $role->permissions;

        if ($role->parent) {
            $permissions = $permissions->merge($this->collectPermissionsForRole($role->parent, $visited));
        }

        return $permissions;
    }

    protected function cacheSupportsTagging(): bool
    {
        return Cache::getStore() instanceof TaggableStore;
    }

    protected function permissionCacheKey(): string
    {
        return 'permissions_for_user_'.$this->getKey();
    }
}
