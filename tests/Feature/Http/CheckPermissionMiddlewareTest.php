<?php

declare( strict_types=1 );

use ArtisanPackUI\Rbac\Models\Permission;
use ArtisanPackUI\Rbac\Models\Role;
use Illuminate\Support\Facades\Route;
use Tests\Models\TestUser;

beforeEach( function (): void {
    Route::get( '/protected-route', fn () => 'Success' )->middleware( 'permission:edit-articles' );
    Route::get( '/multi-permission', fn () => 'Success' )->middleware( 'permission:edit-articles,delete-articles' );
} );

it( 'returns 401 for unauthenticated users', function (): void {
    $this->get( '/protected-route' )->assertStatus( 401 );
} );

it( 'returns 403 when an authenticated user lacks the permission', function (): void {
    $user = TestUser::create( ['name' => 'Test', 'email' => 'test@example.com'] );

    $this->actingAs( $user )
        ->get( '/protected-route' )
        ->assertStatus( 403 );
} );

it( 'returns 200 when the user has the required permission', function (): void {
    $user       = TestUser::create( ['name' => 'Test', 'email' => 'test@example.com'] );
    $role       = Role::create( ['name' => 'editor'] );
    $permission = Permission::create( ['name' => 'edit-articles'] );
    $role->permissions()->attach( $permission );
    $user->roles()->attach( $role );

    $this->actingAs( $user )
        ->get( '/protected-route' )
        ->assertStatus( 200 );
} );

it( 'allows access when the user has any of multiple permissions', function (): void {
    $user       = TestUser::create( ['name' => 'Test', 'email' => 'test@example.com'] );
    $role       = Role::create( ['name' => 'reviewer'] );
    $permission = Permission::create( ['name' => 'delete-articles'] );
    $role->permissions()->attach( $permission );
    $user->roles()->attach( $role );

    $this->actingAs( $user )
        ->get( '/multi-permission' )
        ->assertStatus( 200 );
} );

it( 'denies access when the user has none of the listed permissions', function (): void {
    $user = TestUser::create( ['name' => 'Test', 'email' => 'test@example.com'] );

    $this->actingAs( $user )
        ->get( '/multi-permission' )
        ->assertStatus( 403 );
} );
