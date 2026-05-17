<?php

declare( strict_types=1 );

namespace Tests\Models;

use ArtisanPackUI\Rbac\Models\Role;

class CustomRole extends Role
{
    protected $table = 'roles';

    public function customLabel(): string
    {
        return 'custom:' . $this->name;
    }
}
