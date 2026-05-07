<?php

declare(strict_types=1);

use ArtisanPackUI\Rbac\Models\Permission;
use ArtisanPackUI\Rbac\Models\Role;

return [

    /*
    |--------------------------------------------------------------------------
    | Model Bindings
    |--------------------------------------------------------------------------
    |
    | Concrete classes used for the Role and Permission models. Override these
    | bindings when extending the base models — for example, the CMS framework
    | substitutes its own subclasses so its Users module can attach extra
    | relationships and scopes without forking this package.
    |
    | Any class declared here must extend the corresponding base model so the
    | observers, traits, and migrations continue to work.
    |
    */

    'models' => [
        'role'       => Role::class,
        'permission' => Permission::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Pivot Tables + Foreign Keys
    |--------------------------------------------------------------------------
    |
    | Names of the pivot tables backing the role-permission and role-user
    | relationships, plus the foreign key naming the user side of the
    | role-user pivot. Override these only when integrating with a legacy
    | schema; the defaults match the migrations shipped in this package.
    |
    */

    'tables' => [
        'role_user'       => 'role_user',
        'permission_role' => 'permission_role',
    ],

    'foreign_keys' => [
        'user' => 'user_id',
    ],

    /*
    |--------------------------------------------------------------------------
    | Permission Cache
    |--------------------------------------------------------------------------
    |
    | The HasPermissions trait caches a user's resolved permission list to
    | avoid repeating recursive role-hierarchy walks on every authorization
    | check. The cache is invalidated automatically when roles, permissions,
    | or assignments change.
    |
    */

    'cache' => [
        'user_permissions_ttl' => 60,
        'permission_names_ttl' => 3600,
        'tag'                  => 'rbac',
    ],

];
