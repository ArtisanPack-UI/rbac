<?php

declare(strict_types=1);

namespace ArtisanPackUI\Rbac\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

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
            config('artisanpack.rbac.models.role', Role::class),
            config('artisanpack.rbac.tables.permission_role', 'permission_role'),
        );
    }

    public function scopeWhereSlug(Builder $query, string $slug): Builder
    {
        return $query->where('slug', $slug);
    }

    public static function findBySlug(string $slug): ?static
    {
        return static::query()->where('slug', $slug)->first();
    }

    public function save(array $options = []): bool
    {
        if (empty($this->slug) && ! empty($this->name)) {
            $this->slug = Str::slug($this->name);
        }

        return parent::save($options);
    }

    protected static function boot(): void
    {
        parent::boot();

        static::deleting(function ($permission): void {
            $permission->roles()->detach();
        });
    }
}
