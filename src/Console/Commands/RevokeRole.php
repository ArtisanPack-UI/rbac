<?php

declare(strict_types=1);

namespace ArtisanPackUI\Rbac\Console\Commands;

use ArtisanPackUI\Rbac\Models\Role;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class RevokeRole extends Command
{
    protected $signature = 'user:revoke-role {user : The user ID, email, or username} {role : The role name}';

    protected $description = 'Revoke a role from a user';

    public function handle(): int
    {
        $userModel = config('auth.providers.users.model');
        $roleModel = config('artisanpack.rbac.models.role', Role::class);

        $userArgument = $this->argument('user');
        $role = $roleModel::where('name', $this->argument('role'))->first();

        $user = $this->resolveUser($userModel, $userArgument);

        if (! $user) {
            $this->error('User not found.');

            return self::FAILURE;
        }

        if (! $role) {
            $this->error('Role not found.');

            return self::FAILURE;
        }

        $user->roles()->detach($role->getKey());

        if (method_exists($user, 'flushPermissionCache')) {
            $user->flushPermissionCache();
        }

        $this->info("Role `{$role->name}` revoked from user `{$user->name}` successfully.");

        return self::SUCCESS;
    }

    /**
     * Resolve a user by ID (numeric) or by any of the configured lookup
     * fields. Skips lookup fields whose columns are missing on the users
     * table so this works against any standard Laravel schema.
     *
     * @param  class-string<Model>  $userModel
     */
    protected function resolveUser(string $userModel, string $identifier)
    {
        if (is_numeric($identifier)) {
            return $userModel::find($identifier);
        }

        $table = (new $userModel)->getTable();
        $fields = (array) config('artisanpack.rbac.user_lookup_fields', ['email']);

        $query = $userModel::query();
        $any = false;

        foreach ($fields as $field) {
            if (! Schema::hasColumn($table, $field)) {
                continue;
            }

            $query->orWhere($field, $identifier);
            $any = true;
        }

        return $any ? $query->first() : null;
    }
}
