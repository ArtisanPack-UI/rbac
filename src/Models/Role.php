<?php

declare(strict_types=1);

namespace ArtisanPackUI\Rbac\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Role model — represents a named bundle of permissions optionally arranged
 * in a parent/child hierarchy.
 *
 * Use `config('artisanpack.rbac.models.role')` to substitute a subclass
 * across the package.
 */
class Role extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'parent_id',
    ];

    public function scopeWhereSlug(Builder $query, string $slug): Builder
    {
        return $query->where('slug', $slug);
    }

    public static function findBySlug(string $slug): ?static
    {
        return static::query()->where('slug', $slug)->first();
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            config('artisanpack.rbac.models.permission', Permission::class),
            config('artisanpack.rbac.tables.permission_role', 'permission_role'),
        );
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            config('auth.providers.users.model'),
            config('artisanpack.rbac.tables.role_user', 'role_user'),
            'role_id',
            config('artisanpack.rbac.foreign_keys.user', 'user_id'),
        );
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(static::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(static::class, 'parent_id');
    }

    public function save(array $options = []): bool
    {
        if (empty($this->slug) && ! empty($this->name)) {
            $this->slug = Str::slug($this->name);
        }

        $this->guardAgainstNameSlugCollision();

        return parent::save($options);
    }

    /**
     * Reject saves where this row's name matches another row's slug, or
     * vice versa. The DB unique constraint already covers same-column
     * duplicates; this catches the cross-column case so name-first /
     * slug-fallback lookups remain unambiguous.
     */
    protected function guardAgainstNameSlugCollision(): void
    {
        if (empty($this->name) || empty($this->slug)) {
            return;
        }

        $key = $this->getKey();

        $collision = static::query()
            ->where(function ($query): void {
                $query->where('name', $this->slug)
                    ->orWhere('slug', $this->name);
            })
            ->when($key !== null, fn ($query) => $query->where($this->getKeyName(), '!=', $key))
            ->where(function ($query): void {
                // Self-overlap (name === slug on this same row) is fine —
                // it's the most common case post auto-derivation.
                $query->where('name', '!=', $this->name)
                    ->orWhere('slug', '!=', $this->slug);
            })
            ->exists();

        if ($collision) {
            throw new \RuntimeException(sprintf(
                'Role name/slug collision: another row already uses "%s" or "%s" in the opposite column.',
                $this->name,
                $this->slug,
            ));
        }
    }

    protected static function boot(): void
    {
        parent::boot();

        static::deleting(function ($role): void {
            $role->permissions()->detach();
            $role->users()->detach();
        });
    }
}
