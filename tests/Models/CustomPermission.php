<?php

declare( strict_types=1 );

namespace Tests\Models;

use ArtisanPackUI\Rbac\Models\Permission;

class CustomPermission extends Permission
{
    protected $table = 'permissions';

    public function customLabel(): string
    {
        return 'custom:' . $this->name;
    }
}
