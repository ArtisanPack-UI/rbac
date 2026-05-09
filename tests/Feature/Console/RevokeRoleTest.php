<?php

declare(strict_types=1);

use ArtisanPackUI\Rbac\Models\Role;
use Tests\Models\TestUser;

it('revokes a role from a user', function (): void {
    $user = TestUser::create(['name' => 'Test', 'email' => 'test@example.com']);
    $role = Role::create(['name' => 'admin']);
    $user->roles()->attach($role);

    $this->artisan('user:revoke-role', ['user' => (string) $user->id, 'role' => 'admin'])
        ->assertSuccessful();

    expect($user->fresh()->hasRole('admin'))->toBeFalse();
});

it('fails when the user does not exist', function (): void {
    Role::create(['name' => 'admin']);

    $this->artisan('user:revoke-role', ['user' => '999', 'role' => 'admin'])
        ->assertFailed();
});

it('fails when the role does not exist', function (): void {
    $user = TestUser::create(['name' => 'Test', 'email' => 'test@example.com']);

    $this->artisan('user:revoke-role', ['user' => (string) $user->id, 'role' => 'ghost'])
        ->assertFailed();
});
