<?php

/**
 * `role:create` Artisan command.
 *
 * @package    ArtisanPack_UI
 * @subpackage Rbac
 *
 * @author     Jacob Martella <support@artisanpackui.dev>
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\Rbac\Console\Commands;

use ArtisanPackUI\Rbac\Models\Role;
use Illuminate\Console\Command;

/**
 * Creates a role row. Slug is auto-derived from `name` unless an
 * explicit `--slug` is supplied. Hierarchy is configured via
 * `parent_id` post-creation, not via this command.
 *
 * @since 1.0.0
 */
class CreateRole extends Command
{
    protected $signature = 'role:create {name} {--slug=} {--description=}';

    protected $description = 'Create a new role';

    public function handle(): int
    {
        $model = config( 'artisanpack.rbac.models.role', Role::class );

        $payload = array_map(
            fn ( $value ) => is_string( $value ) ? trim( $value ) : $value,
            [
                'name'        => $this->argument( 'name' ),
                'slug'        => $this->option( 'slug' ),
                'description' => $this->option( 'description' ),
            ],
        );

        $role = $model::create(
            array_filter(
                $payload,
                fn ( $value ) => null !== $value && '' !== $value,
            ),
        );

        $this->info( "Role `{$role->name}` created successfully." );

        return self::SUCCESS;
    }
}
