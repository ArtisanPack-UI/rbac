<?php

declare( strict_types=1 );

namespace ArtisanPackUI\Rbac\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Permission model — represents an atomic capability that can be granted
 * to one or more roles.
 *
 * Use `config('artisanpack.rbac.models.permission')` to substitute a
 * subclass across the package.
 */
class Permission extends Model
{
    protected $fillable = [
        'name',
        'description',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            config( 'artisanpack.rbac.models.role', Role::class ),
            config( 'artisanpack.rbac.tables.permission_role', 'permission_role' ),
        );
    }

    protected static function boot(): void
    {
        parent::boot();

        static::deleting( function ( $permission ): void {
            $permission->roles()->detach();
        } );
    }
}
