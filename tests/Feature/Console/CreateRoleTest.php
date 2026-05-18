<?php

declare( strict_types=1 );

it( 'creates a role via the role:create command', function (): void {
    $this->artisan( 'role:create', ['name' => 'admin'] )
        ->assertSuccessful()
        ->expectsOutputToContain( 'Role `admin` created successfully.' );

    $this->assertDatabaseHas( 'roles', ['name' => 'admin'] );
} );

it( 'stores the description option on the role', function (): void {
    $this->artisan( 'role:create', ['name' => 'editor', '--description' => 'Editor role'] )
        ->assertSuccessful();

    $this->assertDatabaseHas( 'roles', ['name' => 'editor', 'description' => 'Editor role'] );
} );

it( 'auto-derives the slug from the name when --slug is not supplied', function (): void {
    $this->artisan( 'role:create', ['name' => 'Site Owner'] )
        ->assertSuccessful();

    $this->assertDatabaseHas( 'roles', ['name' => 'Site Owner', 'slug' => 'site-owner'] );
} );

it( 'accepts a custom --slug option', function (): void {
    $this->artisan( 'role:create', ['name' => 'Site Owner', '--slug' => 'owner'] )
        ->assertSuccessful();

    $this->assertDatabaseHas( 'roles', ['name' => 'Site Owner', 'slug' => 'owner'] );
} );
