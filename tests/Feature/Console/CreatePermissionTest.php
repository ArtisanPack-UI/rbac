<?php

declare( strict_types=1 );

it( 'creates a permission via the permission:create command', function (): void {
    $this->artisan( 'permission:create', [ 'name' => 'edit-articles' ] )
        ->assertSuccessful()
        ->expectsOutputToContain( 'Permission `edit-articles` created successfully.' );

    $this->assertDatabaseHas( 'permissions', [ 'name' => 'edit-articles' ] );
} );

it( 'stores the description option on the permission', function (): void {
    $this->artisan( 'permission:create', [ 'name' => 'publish', '--description' => 'Publish articles' ] )
        ->assertSuccessful();

    $this->assertDatabaseHas( 'permissions', [ 'name' => 'publish', 'description' => 'Publish articles' ] );
} );
