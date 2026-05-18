<?php

/**
 * HasRoles trait.
 *
 * @package    ArtisanPack_UI
 * @subpackage Rbac
 *
 * @author     Jacob Martella <support@artisanpackui.dev>
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\Rbac\Concerns;

use ArtisanPackUI\Rbac\Models\Role;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;

/**
 * Add role membership to an authenticatable model.
 *
 * Provides the `roles()` relationship plus convenience helpers for checking
 * and mutating role assignments. Pair with {@see HasPermissions} for full
 * RBAC functionality.
 */
trait HasRoles
{
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            $this->resolveRoleModel(),
            $this->getRoleUserPivotTable(),
            $this->getRoleUserForeignKey(),
            'role_id',
        );
    }

    public function hasRole( Role|string|Collection $role ): bool
    {
        if ( is_string( $role ) ) {
            // Prefer name match; fall back to slug. This avoids ambiguity
            // when one row's name happens to equal another row's slug.
            if ( $this->roles->contains( 'name', $role ) ) {
                return true;
            }

            return $this->roles->contains( 'slug', $role );
        }

        if ( $role instanceof Role ) {
            return $this->roles->contains( 'id', $role->getKey() );
        }

        $incomingIds = $role->map->getKey()->all();
        $currentIds  = $this->roles->map->getKey()->all();

        return count( array_intersect( $incomingIds, $currentIds ) ) > 0;
    }

    public function assignRole( Role|string $role ): static
    {
        $resolved = $this->resolveRoleInstance( $role );

        if ( null !== $resolved ) {
            $this->roles()->syncWithoutDetaching( [$resolved->getKey()] );
            Event::dispatch( 'rbac.user.role_assigned', [$this, $resolved] );

            if ( method_exists( $this, 'flushPermissionCache' ) ) {
                $this->flushPermissionCache();
            }
        }

        return $this;
    }

    public function removeRole( Role|string $role ): static
    {
        $resolved = $this->resolveRoleInstance( $role );

        if ( null !== $resolved ) {
            $this->roles()->detach( $resolved->getKey() );
            Event::dispatch( 'rbac.user.role_removed', [$this, $resolved] );

            if ( method_exists( $this, 'flushPermissionCache' ) ) {
                $this->flushPermissionCache();
            }
        }

        return $this;
    }

    protected function getRoleUserPivotTable(): string
    {
        return config( 'artisanpack.rbac.tables.role_user', 'role_user' );
    }

    protected function getRoleUserForeignKey(): string
    {
        return config( 'artisanpack.rbac.foreign_keys.user', 'user_id' );
    }

    /**
     * @return class-string<Model>
     */
    protected function resolveRoleModel(): string
    {
        return config( 'artisanpack.rbac.models.role', Role::class );
    }

    protected function resolveRoleInstance( Role|string $role ): ?Role
    {
        if ( $role instanceof Role ) {
            return $role;
        }

        $model = $this->resolveRoleModel();

        // Prefer name match; fall back to slug. Two queries instead of an
        // orWhere so a `name === other-row's-slug` collision can't return
        // the wrong row.
        return $model::query()->where( 'name', $role )->first()
            ?? $model::query()->where( 'slug', $role )->first();
    }
}
