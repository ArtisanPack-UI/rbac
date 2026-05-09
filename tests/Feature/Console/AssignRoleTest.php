<?php

declare(strict_types=1);

use ArtisanPackUI\Rbac\Models\Role;
use Tests\Models\TestUser;

it('assigns a role to a user by id', function (): void {
    $user = TestUser::create(['name' => 'Test', 'email' => 'test@example.com']);
    Role::create(['name' => 'admin']);

    $this->artisan('user:assign-role', ['user' => (string) $user->id, 'role' => 'admin'])
        ->assertSuccessful();

    expect($user->fresh()->hasRole('admin'))->toBeTrue();
});

it('assigns a role to a user by email', function (): void {
    $user = TestUser::create(['name' => 'Test', 'email' => 'test@example.com']);
    Role::create(['name' => 'admin']);

    $this->artisan('user:assign-role', ['user' => 'test@example.com', 'role' => 'admin'])
        ->assertSuccessful();

    expect($user->fresh()->hasRole('admin'))->toBeTrue();
});

it('is idempotent when assigning the same role twice', function (): void {
    $user = TestUser::create(['name' => 'Test', 'email' => 'test@example.com']);
    Role::create(['name' => 'admin']);

    $this->artisan('user:assign-role', ['user' => (string) $user->id, 'role' => 'admin'])
        ->assertSuccessful();
    $this->artisan('user:assign-role', ['user' => (string) $user->id, 'role' => 'admin'])
        ->assertSuccessful();

    $fresh = $user->fresh();
    $fresh->load('roles');
    expect($fresh->roles)->toHaveCount(1);
});

it('fails when the user does not exist', function (): void {
    Role::create(['name' => 'admin']);

    $this->artisan('user:assign-role', ['user' => '999', 'role' => 'admin'])
        ->assertFailed()
        ->expectsOutputToContain('User not found.');
});

it('fails when the role does not exist', function (): void {
    $user = TestUser::create(['name' => 'Test', 'email' => 'test@example.com']);

    $this->artisan('user:assign-role', ['user' => (string) $user->id, 'role' => 'ghost'])
        ->assertFailed()
        ->expectsOutputToContain('Role not found.');
});
