<?php

declare( strict_types=1 );

use ArtisanPackUI\Rbac\Models\Permission;
use ArtisanPackUI\Rbac\Models\Role;

it( 'creates a permission', function (): void {
    $permission = Permission::create( [ 'name' => 'edit-articles', 'description' => 'Edit articles' ] );

    expect( $permission->name )->toBe( 'edit-articles' );
    $this->assertDatabaseHas( 'permissions', [ 'name' => 'edit-articles' ] );
} );

it( 'belongs to many roles', function (): void {
    $permission = Permission::create( [ 'name' => 'publish' ] );
    $admin      = Role::create( [ 'name' => 'admin' ] );
    $editor     = Role::create( [ 'name' => 'editor' ] );

    $permission->roles()->attach( [ $admin->id, $editor->id ] );
    $permission->load( 'roles' );

    expect( $permission->roles )->toHaveCount( 2 );
} );

it( 'detaches roles when a permission is deleted', function (): void {
    $permission = Permission::create( [ 'name' => 'publish' ] );
    $role       = Role::create( [ 'name' => 'admin' ] );
    $permission->roles()->attach( $role );

    $permission->delete();

    $this->assertDatabaseMissing( 'permission_role', [ 'permission_id' => $permission->id ] );
} );
