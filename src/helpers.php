<?php

/**
 * Rbac helper functions.
 *
 * This file contains global helper functions for the Rbac package.
 * Add custom helper functions below.
 *
 * @package    ArtisanPack_UI
 * @subpackage Rbac
 *
 * @since      1.0.0
 */

use ArtisanPackUI\Rbac\Rbac;

if ( ! function_exists( 'rbac' ) ) {
    /**
     * Get the Rbac instance.
     *
     * @since 1.0.0
     *
     * @return Rbac
     */
    function rbac(): Rbac
    {
        return app( 'rbac' );
    }
}

// Add your custom helper functions below
