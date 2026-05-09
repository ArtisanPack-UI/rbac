<?php

declare(strict_types=1);

namespace ArtisanPackUI\Rbac\Console\Commands;

use ArtisanPackUI\Rbac\Models\Permission;
use Illuminate\Console\Command;

class CreatePermission extends Command
{
    protected $signature = 'permission:create {name} {--description=}';

    protected $description = 'Create a new permission';

    public function handle(): int
    {
        $model = config('artisanpack.rbac.models.permission', Permission::class);

        $permission = $model::create(
            [
                'name' => $this->argument('name'),
                'description' => $this->option('description'),
            ],
        );

        $this->info("Permission `{$permission->name}` created successfully.");

        return self::SUCCESS;
    }
}
