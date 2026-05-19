<?php

declare( strict_types=1 );

use ArtisanPackUI\Rbac\Models\Role;
use Illuminate\Support\Facades\Event;
use Tests\Models\TestUser;

it( 'has a roles relationship', function (): void {
    $user = TestUser::create( ['name' => 'Test', 'email' => 'test@example.com'] );
    $role = Role::create( ['name' => 'admin'] );

    $user->roles()->attach( $role );

    expect( $user->roles )->toHaveCount( 1 );
    expect( $user->roles->first()->name )->toBe( 'admin' );
} );

it( 'returns true for hasRole when the user has the named role', function (): void {
    $user = TestUser::create( ['name' => 'Test', 'email' => 'test@example.com'] );
    $user->roles()->attach( Role::create( ['name' => 'admin'] ) );

    expect( $user->hasRole( 'admin' ) )->toBeTrue();
    expect( $user->hasRole( 'moderator' ) )->toBeFalse();
} );

it( 'accepts a Role instance for hasRole', function (): void {
    $user = TestUser::create( ['name' => 'Test', 'email' => 'test@example.com'] );
    $role = Role::create( ['name' => 'admin'] );
    $user->roles()->attach( $role );

    expect( $user->hasRole( $role ) )->toBeTrue();
    expect( $user->hasRole( Role::create( ['name' => 'other'] ) ) )->toBeFalse();
} );

it( 'accepts a Collection for hasRole and returns true when any match', function (): void {
    $user  = TestUser::create( ['name' => 'Test', 'email' => 'test@example.com'] );
    $admin = Role::create( ['name' => 'admin'] );
    $mod   = Role::create( ['name' => 'moderator'] );
    $user->roles()->attach( $admin );

    expect( $user->hasRole( collect( [$admin, $mod] ) ) )->toBeTrue();
    expect( $user->hasRole( collect( [$mod] ) ) )->toBeFalse();
} );

it( 'assigns a role by name', function (): void {
    $user = TestUser::create( ['name' => 'Test', 'email' => 'test@example.com'] );
    Role::create( ['name' => 'admin'] );

    $user->assignRole( 'admin' );

    expect( $user->fresh()->hasRole( 'admin' ) )->toBeTrue();
} );

it( 'assigns a role by Role instance', function (): void {
    $user = TestUser::create( ['name' => 'Test', 'email' => 'test@example.com'] );
    $role = Role::create( ['name' => 'admin'] );

    $user->assignRole( $role );

    expect( $user->fresh()->hasRole( 'admin' ) )->toBeTrue();
} );

it( 'is idempotent when assigning the same role twice', function (): void {
    $user = TestUser::create( ['name' => 'Test', 'email' => 'test@example.com'] );
    Role::create( ['name' => 'admin'] );

    $user->assignRole( 'admin' );
    $user->assignRole( 'admin' );

    expect( $user->fresh()->roles )->toHaveCount( 1 );
} );

it( 'silently no-ops when assigning an unknown role name', function (): void {
    $user = TestUser::create( ['name' => 'Test', 'email' => 'test@example.com'] );

    $user->assignRole( 'ghost' );

    expect( $user->fresh()->roles )->toHaveCount( 0 );
} );

it( 'removes a role', function (): void {
    $user = TestUser::create( ['name' => 'Test', 'email' => 'test@example.com'] );
    Role::create( ['name' => 'admin'] );
    $user->assignRole( 'admin' );

    $user->removeRole( 'admin' );

    expect( $user->fresh()->hasRole( 'admin' ) )->toBeFalse();
} );

it( 'dispatches events when roles are assigned and removed', function (): void {
    Event::fake();

    $user = TestUser::create( ['name' => 'Test', 'email' => 'test@example.com'] );
    Role::create( ['name' => 'admin'] );

    $user->assignRole( 'admin' );
    $user->removeRole( 'admin' );

    Event::assertDispatched( 'rbac.user.role_assigned' );
    Event::assertDispatched( 'rbac.user.role_removed' );
} );
