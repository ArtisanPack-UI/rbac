<?php

declare( strict_types=1 );

use ArtisanPackUI\Rbac\Models\Permission;
use ArtisanPackUI\Rbac\Models\Role;
use Illuminate\Support\Facades\Gate;
use Tests\Models\TestUser;

it( 'resolves $user->can() through registered RBAC permissions', function (): void {
    $user       = TestUser::create( ['name' => 'Test', 'email' => 'test@example.com'] );
    $role       = Role::create( ['name' => 'editor'] );
    $permission = Permission::create( ['name' => 'edit-articles'] );
    $role->permissions()->attach( $permission );
    $user->roles()->attach( $role );

    expect( $user->can( 'edit-articles' ) )->toBeTrue();
    expect( $user->can( 'delete-articles' ) )->toBeFalse();
} );

it( 'resolves Gate::allows() through registered RBAC permissions', function (): void {
    $user       = TestUser::create( ['name' => 'Test', 'email' => 'test@example.com'] );
    $role       = Role::create( ['name' => 'editor'] );
    $permission = Permission::create( ['name' => 'edit-articles'] );
    $role->permissions()->attach( $permission );
    $user->roles()->attach( $role );

    $this->actingAs( $user );

    expect( Gate::allows( 'edit-articles' ) )->toBeTrue();
    expect( Gate::denies( 'delete-articles' ) )->toBeTrue();
} );

it( 'falls back to standard Gate behavior for non-RBAC abilities', function (): void {
    Gate::define( 'view-dashboard', fn ( $user ) => true );

    $user = TestUser::create( ['name' => 'Test', 'email' => 'test@example.com'] );

    expect( $user->can( 'view-dashboard' ) )->toBeTrue();
} );

it( 'denies non-RBAC abilities with no defined Gate', function (): void {
    $user = TestUser::create( ['name' => 'Test', 'email' => 'test@example.com'] );

    expect( $user->can( 'undefined-ability' ) )->toBeFalse();
} );

it( 'invalidates the permission-name cache when permissions change', function (): void {
    $user = TestUser::create( ['name' => 'Test', 'email' => 'test@example.com'] );
    $role = Role::create( ['name' => 'editor'] );
    $user->roles()->attach( $role );

    expect( $user->can( 'publish' ) )->toBeFalse();

    $permission = Permission::create( ['name' => 'publish'] );
    $role->permissions()->attach( $permission );
    $user->load( 'roles.permissions');
    $user->flushPermissionCache();

    expect( $user->can( 'publish'))->toBeTrue();
});
