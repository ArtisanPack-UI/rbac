<?php

/**
 * `permission:create` Artisan command.
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

use ArtisanPackUI\Rbac\Models\Permission;
use Illuminate\Console\Command;

/**
 * Creates a permission row. Slug is auto-derived from `name` unless
 * an explicit `--slug` is supplied. Permission creation flushes the
 * Gate `before` permission-names cache via the observer.
 *
 * @since 1.0.0
 */
class CreatePermission extends Command
{
    protected $signature = 'permission:create {name} {--slug=} {--description=}';

    protected $description = 'Create a new permission';

    public function handle(): int
    {
        $model = config( 'artisanpack.rbac.models.permission', Permission::class );

        $payload = array_map(
            fn ( $value ) => is_string( $value ) ? trim( $value ) : $value,
            [
                'name'        => $this->argument( 'name' ),
                'slug'        => $this->option( 'slug' ),
                'description' => $this->option( 'description' ),
            ],
        );

        $permission = $model::create(
            array_filter(
                $payload,
                fn ( $value ) => null !== $value && '' !== $value,
            ),
        );

        $this->info( "Permission `{$permission->name}` created successfully." );

        return self::SUCCESS;
    }
}
