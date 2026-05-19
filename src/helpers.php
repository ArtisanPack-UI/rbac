<?php

/**
 * Rbac helper functions.
 *
 * @package    ArtisanPack_UI
 * @subpackage Rbac
 *
 * @author     Jacob Martella <support@artisanpackui.dev>
 *
 * @since      1.0.0
 */

use ArtisanPackUI\Rbac\Rbac;

if ( ! function_exists( 'rbac' ) ) {
    /**
     * Get the Rbac instance.
     *
     * @since 1.0.0
     */
    function rbac(): Rbac
    {
        return app( 'rbac' );
    }
}

// Add your custom helper functions below
