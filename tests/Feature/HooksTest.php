<?php

declare( strict_types=1 );

use ArtisanPackUI\Hooks\Facades\Action;
use ArtisanPackUI\Hooks\Facades\Filter;
use ArtisanPackUI\Rbac\Models\Permission;
use ArtisanPackUI\Rbac\Models\Role;
use Illuminate\Support\Facades\Gate;
use Tests\Models\TestUser;

afterEach( function (): void {
    Action::removeAll( 'ap.rbac.role.assigned' );
    Action::removeAll( 'ap.rbac.role.revoked' );
    Action::removeAll( 'ap.rbac.checkingAbility' );
    Filter::removeAll( 'ap.rbac.abilitiesForUser' );
} );

it( 'fires ap.rbac.role.assigned when a role is assigned', function (): void {
    $captured = [];
    addAction( 'ap.rbac.role.assigned', function ( $user, $role ) use ( &$captured ): void {
        $captured[] = [$user, $role];
    } );

    $user = TestUser::create( ['name' => 'Test', 'email' => 'assigned@example.com'] );
    Role::create( ['name' => 'admin'] );

    $user->assignRole( 'admin' );

    expect( $captured )->toHaveCount( 1 );
    expect( $captured[0][0]->id )->toBe( $user->id );
    expect( $captured[0][1]->name )->toBe( 'admin' );
} );

it( 'does not fire ap.rbac.role.assigned when the role does not exist', function (): void {
    $fired = false;
    addAction( 'ap.rbac.role.assigned', function () use ( &$fired ): void {
        $fired = true;
    } );

    $user = TestUser::create( ['name' => 'Test', 'email' => 'ghost@example.com'] );
    $user->assignRole( 'ghost' );

    expect( $fired )->toBeFalse();
} );

it( 'fires ap.rbac.role.revoked when a role is removed', function (): void {
    $captured = [];
    addAction( 'ap.rbac.role.revoked', function ( $user, $role ) use ( &$captured ): void {
        $captured[] = [$user, $role];
    } );

    $user = TestUser::create( ['name' => 'Test', 'email' => 'revoked@example.com'] );
    Role::create( ['name' => 'admin'] );
    $user->assignRole( 'admin' );

    $user->removeRole( 'admin' );

    expect( $captured )->toHaveCount( 1 );
    expect( $captured[0][1]->name )->toBe( 'admin' );
} );

it( 'exposes ap.rbac.abilitiesForUser as a filter on the resolved list', function (): void {
    $user       = TestUser::create( ['name' => 'Test', 'email' => 'abilities@example.com'] );
    $role       = Role::create( ['name' => 'editor'] );
    $permission = Permission::create( ['name' => 'edit-articles'] );
    $role->permissions()->attach( $permission );
    $user->roles()->attach( $role );

    addFilter( 'ap.rbac.abilitiesForUser', function ( array $abilities, $filteredUser ) {
        $abilities[] = 'grafted-from-ldap';
        return $abilities;
    } );

    $abilities = $user->getAbilities();

    expect( $abilities )->toContain( 'edit-articles' );
    expect( $abilities )->toContain( 'grafted-from-ldap' );
} );

it( 'grafted abilities from the filter satisfy hasPermissionTo and Gate checks', function (): void {
    $user = TestUser::create( ['name' => 'Test', 'email' => 'grafted@example.com'] );
    $role = Role::create( ['name' => 'editor'] );
    $user->roles()->attach( $role );

    addFilter( 'ap.rbac.abilitiesForUser', function ( array $abilities ) {
        $abilities[] = 'ldap-only';
        return $abilities;
    } );

    // Register 'ldap-only' as a known permission so Gate::before matches it.
    Permission::create( ['name' => 'ldap-only'] );

    expect( $user->hasPermissionTo( 'ldap-only' ) )->toBeTrue();
    expect( $user->can( 'ldap-only' ) )->toBeTrue();
} );

it( 'fires ap.rbac.checkingAbility before each Gate check', function (): void {
    $captured = [];
    addAction( 'ap.rbac.checkingAbility', function ( $user, $ability, $arguments ) use ( &$captured ): void {
        $captured[] = [$user->id, $ability, $arguments];
    } );

    $user = TestUser::create( ['name' => 'Test', 'email' => 'checking@example.com'] );
    Permission::create( ['name' => 'publish' ] );

    Gate::forUser( $user )->allows( 'publish' );

    expect( $captured )->toHaveCount( 1 );
    expect( $captured[0][1] )->toBe( 'publish' );
    expect( $captured[0][2] )->toBeArray();
} );
