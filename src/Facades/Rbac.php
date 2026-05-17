<?php

/**
 * Rbac Facade — static accessor for the Rbac class.
 *
 * @package    ArtisanPack_UI
 * @subpackage Rbac
 *
 * @author     Jacob Martella <support@artisanpackui.dev>
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
 * @since      1.0.0
 */
class Rbac extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @since 1.0.0
     */
    protected static function getFacadeAccessor(): string
    {
        return 'rbac';
    }
}
