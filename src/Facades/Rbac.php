<?php

/**
 * Rbac Facade.
 *
 * Provides static access to the Rbac class.
 *
 * @package    ArtisanPack_UI
 * @subpackage Rbac
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\Rbac\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Rbac Facade.
 *
 * @see \ArtisanPackUI\Rbac\Rbac
 *
 * @package    ArtisanPack_UI
 * @subpackage Rbac
 *
 * @since      1.0.0
 */
class Rbac extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @since 1.0.0
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'rbac';
    }
}
