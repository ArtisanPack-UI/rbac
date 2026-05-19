<?php

/**
 * Rbac service provider.
 *
 * Bootstraps the Rbac package by registering services, bindings, migrations,
 * middleware aliases, Artisan commands, Blade directives, and Gate
 * integration.
 *
 * @package    ArtisanPack_UI
 * @subpackage Rbac
 *
 * @author     Jacob Martella <support@artisanpackui.dev>
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\Rbac;

use ArtisanPackUI\Rbac\Console\Commands\AssignRole;
use ArtisanPackUI\Rbac\Console\Commands\CreatePermission;
use ArtisanPackUI\Rbac\Console\Commands\CreateRole;
use ArtisanPackUI\Rbac\Console\Commands\RevokeRole;
use ArtisanPackUI\Rbac\Http\Middleware\CheckPermission;
use ArtisanPackUI\Rbac\Models\Permission;
use ArtisanPackUI\Rbac\Models\Role;
use ArtisanPackUI\Rbac\Observers\PermissionObserver;
use ArtisanPackUI\Rbac\Observers\RoleObserver;
use Illuminate\Cache\TaggableStore;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/**
 * Service provider for the Rbac package.
 *
 *
 * @since      1.0.0
 */
class RbacServiceProvider extends ServiceProvider
{
    /**
     * Registers any application services.
     *
     * @since 1.0.0
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/artisanpack/rbac.php',
            'artisanpack.rbac',
        );

        $this->app->singleton( 'rbac', function ( $app ) {
            return new Rbac;
        } );
    }

    /**
     * Bootstraps any application services.
     *
     * @since 1.0.0
     */
    public function boot(): void
    {
        $this->publishes(
            [
                __DIR__ . '/../config/artisanpack/rbac.php' => config_path( 'artisanpack/rbac.php' ),
            ],
            'rbac-config',
        );

        $this->loadMigrationsFrom( __DIR__ . '/../database/migrations' );

        $this->registerObservers();
        $this->registerMiddleware();
        $this->registerCommands();
        $this->registerBladeDirectives();
        $this->registerGate();
        $this->registerPermissionCacheInvalidators();
    }

    /**
     * Register Eloquent observers on the configured Role and Permission models.
     */
    protected function registerObservers(): void
    {
        $roleModel       = config( 'artisanpack.rbac.models.role', Role::class );
        $permissionModel = config( 'artisanpack.rbac.models.permission', Permission::class );

        $roleModel::observe( RoleObserver::class );
        $permissionModel::observe( PermissionObserver::class );
    }

    /**
     * Register the package's middleware aliases.
     */
    protected function registerMiddleware(): void
    {
        $this->app['router']->aliasMiddleware( 'permission', CheckPermission::class );
    }

    /**
     * Register the package's Artisan commands.
     */
    protected function registerCommands(): void
    {
        if ( ! $this->app->runningInConsole() ) {
            return;
        }

        $this->commands(
            [
                CreateRole::class,
                CreatePermission::class,
                AssignRole::class,
                RevokeRole::class,
            ],
        );
    }

    /**
     * Register the @role and @permission Blade directives.
     */
    protected function registerBladeDirectives(): void
    {
        Blade::directive( 'role', function ( $role ) {
            return "<?php if(auth()->check() && method_exists(auth()->user(), 'hasRole') && auth()->user()->hasRole({$role})): ?>";
        } );

        Blade::directive( 'endrole', function () {
            return '<?php endif; ?>';
        } );

        Blade::directive( 'permission', function ( $permission ) {
            return "<?php if(auth()->check() && auth()->user()->can({$permission})): ?>";
        } );

        Blade::directive( 'endpermission', function () {
            return '<?php endif; ?>';
        } );
    }

    /**
     * Register the Gate::before integration so RBAC permissions resolve via
     * Laravel's authorization gate. Returns null for unmatched abilities so
     * normal Gate/Policy checks continue to run.
     */
    protected function registerGate(): void
    {
        Gate::before( function ( $user, $ability ) {
            if ( ! method_exists( $user, 'hasPermissionTo' ) ) {
                return null;
            }

            $permissionNames = $this->getCachedPermissionNames();

            if ( in_array( $ability, $permissionNames, true ) ) {
                return $user->hasPermissionTo( $ability );
            }

            return null;
        } );
    }

    /**
     * Flush the permission-name cache whenever a Permission record changes
     * so Gate checks don't operate on stale data.
     */
    protected function registerPermissionCacheInvalidators(): void
    {
        $permissionModel = config( 'artisanpack.rbac.models.permission', Permission::class );

        $permissionModel::created( fn () => $this->flushPermissionNamesCache() );
        $permissionModel::updated( fn () => $this->flushPermissionNamesCache() );
        $permissionModel::deleted( fn () => $this->flushPermissionNamesCache() );
    }

    /**
     * Get the cached list of permission names for fast Gate checks.
     *
     * @return array<int, string>
     */
    protected function getCachedPermissionNames(): array
    {
        $permissionModel = config( 'artisanpack.rbac.models.permission', Permission::class );
        $cacheKey        = 'rbac_permission_names';
        $ttl             = (int) config( 'artisanpack.rbac.cache.permission_names_ttl', 3600 );

        if ( Cache::getStore() instanceof TaggableStore ) {
            return Cache::tags( [config( 'artisanpack.rbac.cache.tag', 'rbac' )] )
                ->remember( $cacheKey, $ttl, fn () => array_values( array_unique( array_merge(
                    $permissionModel::pluck( 'name' )->toArray(),
                    $permissionModel::pluck( 'slug' )->toArray(),
                ) ) ) );
        }

        return Cache::remember( $cacheKey, $ttl, fn () => array_values( array_unique( array_merge(
            $permissionModel::pluck( 'name' )->toArray(),
            $permissionModel::pluck( 'slug' )->toArray(),
        ) ) ) );
    }

    /**
     * Flush the cached permission names.
     */
    protected function flushPermissionNamesCache(): void
    {
        $cacheKey = 'rbac_permission_names';
        $tag      = config( 'artisanpack.rbac.cache.tag', 'rbac' );

        if ( Cache::getStore() instanceof TaggableStore ) {
            Cache::tags( [$tag] )->forget( $cacheKey );

            return;
        }

        Cache::forget( $cacheKey );
    }
}
