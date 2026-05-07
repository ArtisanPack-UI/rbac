<?php

declare( strict_types=1 );

namespace ArtisanPackUI\Rbac\Observers;

use ArtisanPackUI\Rbac\Models\Permission;
use Illuminate\Support\Facades\Event;

/**
 * Permission observer — emits package-level events when permission records
 * or pivot memberships change. Consumers listen on the `rbac.permission.*`
 * channel to layer on auditing.
 */
class PermissionObserver
{
    public function created( Permission $permission ): void
    {
        Event::dispatch( 'rbac.permission.created', [ $permission ] );
    }

    public function updated( Permission $permission ): void
    {
        Event::dispatch(
            'rbac.permission.updated',
            [ $permission, $permission->getChanges(), $permission->getOriginal() ],
        );
    }

    public function deleted( Permission $permission ): void
    {
        Event::dispatch( 'rbac.permission.deleted', [ $permission ] );
    }

    public function pivotAttached( Permission $permission, string $relationName, array $pivotIds ): void
    {
        Event::dispatch( 'rbac.permission.pivot_attached', [ $permission, $relationName, $pivotIds ] );
    }

    public function pivotDetached( Permission $permission, string $relationName, array $pivotIds ): void
    {
        Event::dispatch( 'rbac.permission.pivot_detached', [ $permission, $relationName, $pivotIds ] );
    }
}
