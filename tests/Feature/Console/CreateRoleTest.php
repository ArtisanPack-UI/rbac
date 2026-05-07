<?php

declare( strict_types=1 );

it( 'creates a role via the role:create command', function (): void {
    $this->artisan( 'role:create', [ 'name' => 'admin' ] )
        ->assertSuccessful()
        ->expectsOutputToContain( 'Role `admin` created successfully.' );

    $this->assertDatabaseHas( 'roles', [ 'name' => 'admin' ] );
} );

it( 'stores the description option on the role', function (): void {
    $this->artisan( 'role:create', [ 'name' => 'editor', '--description' => 'Editor role' ] )
        ->assertSuccessful();

    $this->assertDatabaseHas( 'roles', [ 'name' => 'editor', 'description' => 'Editor role' ] );
} );
