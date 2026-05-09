<?php

declare(strict_types=1);

use ArtisanPackUI\Rbac\Models\Permission;
use ArtisanPackUI\Rbac\Models\Role;
use Tests\Models\TestUser;

it('renders the @role block when the user has the role', function (): void {
    $user = TestUser::create(['name' => 'Test', 'email' => 'test@example.com']);
    $user->roles()->attach(Role::create(['name' => 'admin']));
    $this->actingAs($user);

    $rendered = view()->file(__DIR__.'/../views/role-directive.blade.php')->render();

    expect($rendered)->toContain('User is an admin');
    expect($rendered)->not->toContain('User is a moderator');
});

it('renders nothing for unauthenticated users in @role blocks', function (): void {
    Role::create(['name' => 'admin']);

    $rendered = view()->file(__DIR__.'/../views/role-directive.blade.php')->render();

    expect($rendered)->not->toContain('User is an admin');
    expect($rendered)->not->toContain('User is a moderator');
});

it('renders the @permission block when the user has the permission', function (): void {
    $user = TestUser::create(['name' => 'Test', 'email' => 'test@example.com']);
    $role = Role::create(['name' => 'editor']);
    $permission = Permission::create(['name' => 'edit-articles']);
    $role->permissions()->attach($permission);
    $user->roles()->attach($role);
    $this->actingAs($user);

    $rendered = view()->file(__DIR__.'/../views/permission-directive.blade.php')->render();

    expect($rendered)->toContain('User can edit articles');
    expect($rendered)->not->toContain('User can delete articles');
});

it('renders nothing for unauthenticated users in @permission blocks', function (): void {
    Permission::create(['name' => 'edit-articles']);

    $rendered = view()->file(__DIR__.'/../views/permission-directive.blade.php')->render();

    expect($rendered)->not->toContain('User can edit articles');
});
