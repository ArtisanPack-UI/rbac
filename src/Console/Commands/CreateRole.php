<?php

declare(strict_types=1);

namespace ArtisanPackUI\Rbac\Console\Commands;

use ArtisanPackUI\Rbac\Models\Role;
use Illuminate\Console\Command;

class CreateRole extends Command
{
    protected $signature = 'role:create {name} {--slug=} {--description=}';

    protected $description = 'Create a new role';

    public function handle(): int
    {
        $model = config('artisanpack.rbac.models.role', Role::class);

        $role = $model::create(
            array_filter(
                [
                    'name' => $this->argument('name'),
                    'slug' => $this->option('slug'),
                    'description' => $this->option('description'),
                ],
                fn ($value) => $value !== null && $value !== '',
            ),
        );

        $this->info("Role `{$role->name}` created successfully.");

        return self::SUCCESS;
    }
}
