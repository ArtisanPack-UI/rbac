<?php

declare(strict_types=1);

namespace ArtisanPackUI\Rbac\Console\Commands;

use ArtisanPackUI\Rbac\Models\Role;
use Illuminate\Console\Command;

class CreateRole extends Command
{
    protected $signature = 'role:create {name} {--description=}';

    protected $description = 'Create a new role';

    public function handle(): int
    {
        $model = config('artisanpack.rbac.models.role', Role::class);

        $role = $model::create(
            [
                'name' => $this->argument('name'),
                'description' => $this->option('description'),
            ],
        );

        $this->info("Role `{$role->name}` created successfully.");

        return self::SUCCESS;
    }
}
