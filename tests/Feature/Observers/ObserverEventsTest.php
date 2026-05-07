<?php

declare( strict_types=1 );

use ArtisanPackUI\Rbac\Models\Permission;
use ArtisanPackUI\Rbac\Models\Role;
use Illuminate\Support\Facades\Event;

/**
 * Capture-based assertions: Event::fake() also intercepts the eloquent.* model
 * events that drive the observers, so we instead listen for the package
 * events directly and assert they fired.
 */
function captureEvent( string $name ): stdClass
{
    $captured        = new stdClass();
    $captured->fired = false;

    Event::listen( $name, function () use ( $captured ): void {
        $captured->fired = true;
    } );

    return $captured;
}

it( 'dispatches rbac.role.created when a role is created', function (): void {
    $captured = captureEvent( 'rbac.role.created' );

    Role::create( [ 'name' => 'admin' ] );

    expect( $captured->fired )->toBeTrue();
} );

it( 'dispatches rbac.role.updated when a role is updated', function (): void {
    $role     = Role::create( [ 'name' => 'admin' ] );
    $captured = captureEvent( 'rbac.role.updated' );

    $role->update( [ 'description' => 'Administrator' ] );

    expect( $captured->fired )->toBeTrue();
} );

it( 'dispatches rbac.role.deleted when a role is deleted', function (): void {
    $role     = Role::create( [ 'name' => 'admin' ] );
    $captured = captureEvent( 'rbac.role.deleted' );

    $role->delete();

    expect( $captured->fired )->toBeTrue();
} );

it( 'dispatches rbac.permission.created when a permission is created', function (): void {
    $captured = captureEvent( 'rbac.permission.created' );

    Permission::create( [ 'name' => 'edit-articles' ] );

    expect( $captured->fired )->toBeTrue();
} );

it( 'dispatches rbac.permission.updated when a permission is updated', function (): void {
    $permission = Permission::create( [ 'name' => 'edit-articles' ] );
    $captured   = captureEvent( 'rbac.permission.updated' );

    $permission->update( [ 'description' => 'Edit articles' ] );

    expect( $captured->fired )->toBeTrue();
} );

it( 'dispatches rbac.permission.deleted when a permission is deleted', function (): void {
    $permission = Permission::create( [ 'name' => 'edit-articles' ] );
    $captured   = captureEvent( 'rbac.permission.deleted' );

    $permission->delete();

    expect( $captured->fired )->toBeTrue();
} );
