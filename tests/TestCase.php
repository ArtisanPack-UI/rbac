<?php

declare( strict_types=1 );

namespace Tests;

use ArtisanPackUI\Rbac\RbacServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Tests\Models\TestUser;

/**
 * Base Test Case
 *
 * Provides base functionality for all Rbac package tests. Boots an in-memory
 * SQLite database with a minimal `users` table and runs the package
 * migrations so role/permission tables are available.
 *
 * @since 1.0.0
 */
abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Gets package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app  The application instance.
     *
     * @return array<int, class-string> Array of service provider class names.
     */
    protected function getPackageProviders( $app ): array
    {
        return [
            RbacServiceProvider::class,
        ];
    }

    /**
     * Defines environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app  The application instance.
     */
    protected function defineEnvironment( $app ): void
    {
        $app['config']->set( 'app.key', 'base64:' . base64_encode( random_bytes( 32 ) ) );

        $app['config']->set( 'database.default', 'testbench' );
        $app['config']->set( 'database.connections.testbench', [
            'driver'                  => 'sqlite',
            'database'                => ':memory:',
            'prefix'                  => '',
            'foreign_key_constraints' => true,
        ] );

        $app['config']->set( 'auth.providers.users.model', TestUser::class );
    }

    /**
     * Defines database setup.
     *
     * Creates a minimal `users` table so authentication-aware tests have
     * somewhere to insert TestUser records.
     */
    protected function defineDatabaseMigrations(): void
    {
        $this->app['db']->connection()->getSchemaBuilder()->create( 'users', function ( Blueprint $table ): void {
            $table->increments( 'id' );
            $table->string( 'name' );
            $table->string( 'email' )->unique();
            $table->string( 'password' )->nullable();
            $table->timestamps();
        } );
    }
}
