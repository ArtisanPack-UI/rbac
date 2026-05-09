<?php

declare(strict_types=1);

use ArtisanPackUI\Rbac\Models\Permission;
use ArtisanPackUI\Rbac\Models\Role;
use Tests\Models\TestUser;

it('returns true for permissions held through a directly-assigned role', function (): void {
    $user = TestUser::create(['name' => 'Test', 'email' => 'test@example.com']);
    $role = Role::create(['name' => 'editor']);
    $permission = Permission::create(['name' => 'edit-articles']);
    $role->permissions()->attach($permission);
    $user->roles()->attach($role);

    expect($user->hasPermissionTo('edit-articles'))->toBeTrue();
    expect($user->hasPermissionTo('delete-articles'))->toBeFalse();
});

it('aliases hasPermission to hasPermissionTo', function (): void {
    $user = TestUser::create(['name' => 'Test', 'email' => 'test@example.com']);
    $role = Role::create(['name' => 'editor']);
    $permission = Permission::create(['name' => 'edit-articles']);
    $role->permissions()->attach($permission);
    $user->roles()->attach($role);

    expect($user->hasPermission('edit-articles'))->toBeTrue();
});

it('walks the role hierarchy when resolving permissions', function (): void {
    $user = TestUser::create(['name' => 'Test', 'email' => 'test@example.com']);
    $admin = Role::create(['name' => 'admin']);
    $editor = Role::create(['name' => 'editor', 'parent_id' => $admin->id]);
    $permission = Permission::create(['name' => 'edit-articles']);
    $admin->permissions()->attach($permission);
    $user->roles()->attach($editor);

    expect($user->hasPermissionTo('edit-articles'))->toBeTrue();
});

it('does not loop when role hierarchy contains a cycle', function (): void {
    $user = TestUser::create(['name' => 'Test', 'email' => 'test@example.com']);
    $a = Role::create(['name' => 'a']);
    $b = Role::create(['name' => 'b', 'parent_id' => $a->id]);
    $a->update(['parent_id' => $b->id]);

    $permission = Permission::create(['name' => 'edit-articles']);
    $a->permissions()->attach($permission);
    $user->roles()->attach($b);

    expect($user->hasPermissionTo('edit-articles'))->toBeTrue();
    expect($user->hasPermissionTo('unknown'))->toBeFalse();
});

it('flushes the permission cache on demand', function (): void {
    $user = TestUser::create(['name' => 'Test', 'email' => 'test@example.com']);
    $role = Role::create(['name' => 'editor']);
    $permission = Permission::create(['name' => 'edit-articles']);
    $role->permissions()->attach($permission);
    $user->roles()->attach($role);
    $user->load('roles.permissions');

    expect($user->hasPermissionTo('edit-articles'))->toBeTrue();

    $role->permissions()->detach($permission);
    $user->load('roles.permissions');
    $user->flushPermissionCache();

    expect($user->hasPermissionTo('edit-articles'))->toBeFalse();
});
