<?php

declare(strict_types=1);

namespace Tests\Models;

use ArtisanPackUI\Rbac\Concerns\HasPermissions;
use ArtisanPackUI\Rbac\Concerns\HasRoles;
use Illuminate\Foundation\Auth\User;

class TestUser extends User
{
    use HasPermissions;
    use HasRoles;

    protected $table = 'users';

    protected $fillable = [
        'id',
        'name',
        'email',
        'password',
    ];
}
