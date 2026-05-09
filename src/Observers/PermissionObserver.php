<?php

declare(strict_types=1);

namespace ArtisanPackUI\Rbac\Observers;

use ArtisanPackUI\Rbac\Models\Permission;
use Illuminate\Support\Facades\Event;

/**
 * Permission observer — emits package-level events when permission records
 * change. Consumers listen on the `rbac.permission.*` channel to layer on
 * auditing. Laravel does not fire pivot events natively, so role/permission
 * pivot mutations are dispatched from the call sites that mutate them.
 */
class PermissionObserver
{
    public function created(Permission $permission): void
    {
        Event::dispatch('rbac.permission.created', [$permission]);
    }

    public function updated(Permission $permission): void
    {
        Event::dispatch(
            'rbac.permission.updated',
            [$permission, $permission->getChanges(), $permission->getOriginal()],
        );
    }

    public function deleted(Permission $permission): void
    {
        Event::dispatch('rbac.permission.deleted', [$permission]);
    }
}
