<?php

declare( strict_types=1 );

use ArtisanPackUI\Rbac\Models\Permission;
use ArtisanPackUI\Rbac\Models\Role;
use Illuminate\Support\Facades\Config;
use Tests\Models\CustomPermission;
use Tests\Models\CustomRole;
use Tests\Models\TestUser;

it( 'role:create command honors a configured custom role model', function (): void {
    Config::set( 'artisanpack.rbac.models.role', CustomRole::class );

    $this->artisan( 'role:create', [ 'name' => 'admin' ] )->assertSuccessful();

    $role = CustomRole::where( 'name', 'admin' )->first();
    expect( $role )->toBeInstanceOf( CustomRole::class );
    expect( $role->customLabel() )->toBe( 'custom:admin' );
} );

it( 'permission:create command honors a configured custom permission model', function (): void {
    Config::set( 'artisanpack.rbac.models.permission', CustomPermission::class );

    $this->artisan( 'permission:create', [ 'name' => 'edit-articles' ] )->assertSuccessful();

    $permission = CustomPermission::where( 'name', 'edit-articles' )->first();
    expect( $permission )->toBeInstanceOf( CustomPermission::class );
} );

it( 'HasRoles trait resolves role lookups against the configured model', function (): void {
    Config::set( 'artisanpack.rbac.models.role', CustomRole::class );

    $user = TestUser::create( [ 'name' => 'Test', 'email' => 'test@example.com' ] );
    CustomRole::create( [ 'name' => 'admin' ] );

    $user->assignRole( 'admin' );

    expect( $user->fresh()->hasRole( 'admin' ) )->toBeTrue();
} );

it( 'falls back to base models when no override is configured', function (): void {
    expect( Config::get( 'artisanpack.rbac.models.role' ) )->toBe( Role::class );
    expect( Config::get( 'artisanpack.rbac.models.permission' ) )->toBe( Permission::class );
} );
