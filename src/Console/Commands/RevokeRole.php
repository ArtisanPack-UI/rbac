<?php

declare( strict_types=1 );

namespace ArtisanPackUI\Rbac\Console\Commands;

use ArtisanPackUI\Rbac\Models\Role;
use Illuminate\Console\Command;

class RevokeRole extends Command
{
    protected $signature = 'user:revoke-role {user : The user ID, email, or username} {role : The role name}';

    protected $description = 'Revoke a role from a user';

    public function handle(): int
    {
        $userModel = config( 'auth.providers.users.model' );
        $roleModel = config( 'artisanpack.rbac.models.role', Role::class );

        $userArgument = $this->argument( 'user' );
        $role         = $roleModel::where( 'name', $this->argument( 'role' ) )->first();

        $user = is_numeric( $userArgument )
            ? $userModel::find( $userArgument )
            : $userModel::where( 'email', $userArgument )->orWhere( 'username', $userArgument )->first();

        if ( ! $user ) {
            $this->error( 'User not found.' );

            return self::FAILURE;
        }

        if ( ! $role ) {
            $this->error( 'Role not found.' );

            return self::FAILURE;
        }

        $user->roles()->detach( $role->getKey() );

        if ( method_exists( $user, 'flushPermissionCache' ) ) {
            $user->flushPermissionCache();
        }

        $this->info( "Role `{$role->name}` revoked from user `{$user->name}` successfully." );

        return self::SUCCESS;
    }
}
