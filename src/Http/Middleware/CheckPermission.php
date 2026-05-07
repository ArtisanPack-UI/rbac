<?php

declare( strict_types=1 );

namespace ArtisanPackUI\Rbac\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Route middleware that enforces RBAC permission checks.
 *
 * Aliased as `permission` by the service provider. Accepts one or more
 * permission names — the user must hold at least one to proceed.
 *
 *     Route::get('/admin', ...)->middleware('permission:posts.publish');
 *     Route::get('/admin', ...)->middleware('permission:posts.publish|posts.review');
 */
class CheckPermission
{
    public function handle( Request $request, Closure $next, string ...$permissions )
    {
        if ( Auth::guest() ) {
            abort( 401 );
        }

        $user = Auth::user();

        foreach ( $permissions as $permission ) {
            if ( $user->can( $permission ) ) {
                return $next( $request );
            }
        }

        abort( 403 );
    }
}
