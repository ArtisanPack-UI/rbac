<?php

declare( strict_types=1 );

use ArtisanPackUI\Rbac\Models\Permission;
use ArtisanPackUI\Rbac\Models\Role;
use Tests\Models\TestUser;

it( 'derives slug from name when slug is not provided on Role', function (): void {
    $role = Role::create( ['name' => 'Site Owner'] );

    expect( $role->slug )->toBe( 'site-owner' );
} );

it( 'derives slug from name when slug is not provided on Permission', function (): void {
    $permission = Permission::create( ['name' => 'Manage Users'] );

    expect( $permission->slug )->toBe( 'manage-users' );
} );

it( 'preserves a slug supplied at creation time on Role', function (): void {
    $role = Role::create( ['name' => 'Site Owner', 'slug' => 'owner'] );

    expect( $role->slug )->toBe( 'owner' );
} );

it( 'preserves a slug supplied at creation time on Permission', function (): void {
    $permission = Permission::create( ['name' => 'Manage Users', 'slug' => 'users.manage'] );

    expect( $permission->slug )->toBe( 'users.manage' );
} );

it( 'looks up a Role by slug via findBySlug', function (): void {
    Role::create( ['name' => 'Editor', 'slug' => 'editor'] );

    $found = Role::findBySlug( 'editor' );

    expect( $found )->not->toBeNull()
        ->and( $found->name )->toBe( 'Editor' );
} );

it( 'looks up a Permission by slug via findBySlug', function (): void {
    Permission::create( ['name' => 'Publish Pages', 'slug' => 'pages.publish'] );

    $found = Permission::findBySlug( 'pages.publish' );

    expect( $found )->not->toBeNull()
        ->and( $found->name )->toBe( 'Publish Pages' );
} );

it( 'matches hasRole by slug as well as name', function (): void {
    $user = TestUser::create( ['name' => 'Test', 'email' => 'slug-role@example.com'] );
    Role::create( ['name' => 'Editor', 'slug' => 'editor'] );

    $user->assignRole( 'editor' );

    expect( $user->hasRole( 'editor' ) )->toBeTrue()
        ->and( $user->hasRole( 'Editor' ) )->toBeTrue();
} );

it( 'matches hasPermissionTo by slug as well as name', function (): void {
    $user       = TestUser::create( ['name' => 'Test', 'email' => 'slug-perm@example.com'] );
    $role       = Role::create( ['name' => 'Editor', 'slug' => 'editor'] );
    $permission = Permission::create( ['name' => 'Publish Pages', 'slug' => 'pages.publish'] );

    $role->permissions()->attach( $permission );
    $user->assignRole( 'editor' );

    expect( $user->hasPermissionTo( 'pages.publish' ) )->toBeTrue()
        ->and( $user->hasPermissionTo( 'Publish Pages' ) )->toBeTrue();
} );

it( 'rejects a Role save that would collide its name with another row\'s slug', function (): void {
    Role::create( ['name' => 'Editor', 'slug' => 'editor'] );

    expect( fn () => Role::create( ['name' => 'editor', 'slug' => 'second'] ) )
        ->toThrow( RuntimeException::class );
} );

it( 'rejects a Permission save that would collide its slug with another row\'s name', function (): void {
    Permission::create( ['name' => 'Publish Pages', 'slug' => 'pages.publish'] );

    expect( fn () => Permission::create( ['name' => 'Other', 'slug' => 'Publish Pages'] ) )
        ->toThrow( RuntimeException::class );
} );

it( 'allows a Role save where name and slug are equal on the same row', function (): void {
    // Most common post-auto-derivation case: name === slug on the same
    // row. The collision guard must allow this.
    $role = Role::create( ['name' => 'editor', 'slug' => 'editor'] );

    expect( $role->name )->toBe( 'editor' )
        ->and( $role->slug )->toBe( 'editor' );
} );

it( 'resolves hasRole to the name-matched row when another row has the same string as a slug', function (): void {
    // Cross-row collision regression: Role A has name='shipper', Role B
    // has name='Shipping' but slug='shipper'. assignRole('shipper') must
    // resolve to Role A (name match wins).
    $a = Role::create( ['name' => 'shipper', 'slug' => 'shipper-role'] );
    Role::create( ['name' => 'Shipping', 'slug' => 'shipper-2'] );

    $user = TestUser::create( ['name' => 'Test', 'email' => 'collision@example.com'] );
    $user->assignRole( 'shipper' );

    expect( $user->roles->pluck( 'id' )->all() )->toBe( [$a->id] );
} );
