<?php

declare( strict_types=1 );

use ArtisanPackUI\Rbac\Models\Permission;
use ArtisanPackUI\Rbac\Models\Role;

it( 'creates a role', function (): void {
    $role = Role::create( [ 'name' => 'admin', 'description' => 'Administrator' ] );

    expect( $role->name )->toBe( 'admin' );
    expect( $role->description )->toBe( 'Administrator' );
    $this->assertDatabaseHas( 'roles', [ 'name' => 'admin' ] );
} );

it( 'attaches permissions to a role', function (): void {
    $role       = Role::create( [ 'name' => 'editor' ] );
    $permission = Permission::create( [ 'name' => 'edit-articles' ] );

    $role->permissions()->attach( $permission );

    expect( $role->permissions )->toHaveCount( 1 );
    expect( $role->permissions->first()->name )->toBe( 'edit-articles' );
} );

it( 'supports parent/child role hierarchy', function (): void {
    $admin  = Role::create( [ 'name' => 'admin' ] );
    $editor = Role::create( [ 'name' => 'editor', 'parent_id' => $admin->id ] );

    expect( $editor->parent->id )->toBe( $admin->id );
    expect( $admin->children->first()->id )->toBe( $editor->id );
} );

it( 'detaches permissions when a role is deleted', function (): void {
    $role       = Role::create( [ 'name' => 'admin' ] );
    $permission = Permission::create( [ 'name' => 'edit-articles' ] );
    $role->permissions()->attach( $permission );

    $role->delete();

    $this->assertDatabaseMissing( 'permission_role', [ 'role_id' => $role->id ] );
} );

it( 'nulls parent_id on children when a parent role is deleted', function (): void {
    $admin  = Role::create( [ 'name' => 'admin' ] );
    $editor = Role::create( [ 'name' => 'editor', 'parent_id' => $admin->id ] );

    $admin->delete();

    expect( $editor->fresh()->parent_id )->toBeNull();
} );
