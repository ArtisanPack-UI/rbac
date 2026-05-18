<?php

/**
 * Permission Eloquent model.
 *
 * @package    ArtisanPack_UI
 * @subpackage Rbac
 *
 * @author     Jacob Martella <support@artisanpackui.dev>
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\Rbac\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use RuntimeException;

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
        'slug',
        'description',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            config( 'artisanpack.rbac.models.role', Role::class ),
            config( 'artisanpack.rbac.tables.permission_role', 'permission_role' ),
        );
    }

    public function scopeWhereSlug( Builder $query, string $slug ): Builder
    {
        return $query->where( 'slug', $slug );
    }

    public static function findBySlug( string $slug ): ?static
    {
        return static::query()->where( 'slug', $slug )->first();
    }

    public function save( array $options = [] ): bool
    {
        if ( empty( $this->slug ) && ! empty( $this->name ) ) {
            $this->slug = Str::slug( $this->name );
        }

        $this->guardAgainstNameSlugCollision();

        return parent::save( $options );
    }

    /**
     * Reject saves where this row's name matches another row's slug, or
     * vice versa. The DB unique constraint already covers same-column
     * duplicates; this catches the cross-column case so name-first /
     * slug-fallback lookups remain unambiguous.
     */
    protected function guardAgainstNameSlugCollision(): void
    {
        if ( empty( $this->name ) || empty( $this->slug ) ) {
            return;
        }

        $key = $this->getKey();

        $collision = static::query()
            ->where( function ( $query ): void {
                $query->where( 'name', $this->slug )
                    ->orWhere( 'slug', $this->name );
            } )
            ->when( null !== $key, fn ( $query ) => $query->where( $this->getKeyName(), '!=', $key ) )
            ->where( function ( $query ): void {
                // Self-overlap (name === slug on this same row) is fine —
                // it's the most common case post auto-derivation.
                $query->where( 'name', '!=', $this->name )
                    ->orWhere( 'slug', '!=', $this->slug );
            } )
            ->exists();

        if ( $collision ) {
            throw new RuntimeException( sprintf(
                'Permission name/slug collision: another row already uses "%s" or "%s" in the opposite column.',
                $this->name,
                $this->slug,
            ) );
        }
    }

    protected static function boot(): void
    {
        parent::boot();

        static::deleting( function ( $permission ): void {
            $permission->roles()->detach();
        });
    }
}
