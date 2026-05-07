<?php

/**
 * Rbac service provider.
 *
 * Bootstraps the Rbac package by registering services and bindings.
 *
 * @package    ArtisanPack_UI
 * @subpackage Rbac
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\Rbac;

use Illuminate\Support\ServiceProvider;

/**
 * Service provider for the Rbac package.
 *
 * Bootstraps the Rbac package by registering services, bindings, migrations,
 * routes, and other package-level integrations as they are added.
 *
 * @package    ArtisanPack_UI
 * @subpackage Rbac
 *
 * @since      1.0.0
 */
class RbacServiceProvider extends ServiceProvider
{
    /**
     * Registers any application services.
     *
     * Binds the Rbac class as a singleton in the container.
     * Add additional service registrations here.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton( 'rbac', function ( $app ) {
            return new Rbac();
        } );
    }

    /**
     * Bootstraps any application services.
     *
     * Add package bootstrapping here such as:
     * - Configuration publishing: $this->publishes([...])
     * - Migration loading: $this->loadMigrationsFrom(...)
     * - Blade directives: Blade::directive(...)
     * - Middleware aliases: $router->aliasMiddleware(...)
     * - Gate integration: Gate::before(...)
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function boot(): void
    {
        // Add your package bootstrapping here
    }
}
