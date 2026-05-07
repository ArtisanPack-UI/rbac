<?php

declare( strict_types=1 );

use ArtisanPackUI\Rbac\Models\Permission;
use ArtisanPackUI\Rbac\Models\Role;

it( 'declares the expected fillable attributes on Role', function (): void {
    expect( ( new Role() )->getFillable() )->toBe( [ 'name', 'description', 'parent_id' ] );
} );

it( 'declares the expected fillable attributes on Permission', function (): void {
    expect( ( new Permission() )->getFillable() )->toBe( [ 'name', 'description' ] );
} );
