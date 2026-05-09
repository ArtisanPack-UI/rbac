<?php

declare(strict_types=1);

namespace ArtisanPackUI\Rbac\Console\Commands;

use ArtisanPackUI\Rbac\Models\Permission;
use Illuminate\Console\Command;

class CreatePermission extends Command
{
    protected $signature = 'permission:create {name} {--slug=} {--description=}';

    protected $description = 'Create a new permission';

    public function handle(): int
    {
        $model = config('artisanpack.rbac.models.permission', Permission::class);

        $payload = array_map(
            fn ($value) => is_string($value) ? trim($value) : $value,
            [
                'name' => $this->argument('name'),
                'slug' => $this->option('slug'),
                'description' => $this->option('description'),
            ],
        );

        $permission = $model::create(
            array_filter(
                $payload,
                fn ($value) => $value !== null && $value !== '',
            ),
        );

        $this->info("Permission `{$permission->name}` created successfully.");

        return self::SUCCESS;
    }
}
