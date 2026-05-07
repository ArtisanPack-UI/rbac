<?php

declare( strict_types=1 );

namespace ArtisanPackUI\Rbac\Observers;

use ArtisanPackUI\Rbac\Models\Role;
use Illuminate\Support\Facades\Event;

/**
 * Role observer — emits package-level events when role records or pivot
 * memberships change. Consumers (e.g. security-analytics) listen on the
 * `rbac.role.*` channel to layer on auditing.
 */
class RoleObserver
{
    public function created( Role $role ): void
    {
        Event::dispatch( 'rbac.role.created', [ $role ] );
    }

    public function updated( Role $role ): void
    {
        Event::dispatch( 'rbac.role.updated', [ $role, $role->getChanges(), $role->getOriginal() ] );
    }

    public function deleted( Role $role ): void
    {
        Event::dispatch( 'rbac.role.deleted', [ $role ] );
    }

    public function pivotAttached( Role $role, string $relationName, array $pivotIds ): void
    {
        Event::dispatch( 'rbac.role.pivot_attached', [ $role, $relationName, $pivotIds ] );
    }

    public function pivotDetached( Role $role, string $relationName, array $pivotIds ): void
    {
        Event::dispatch( 'rbac.role.pivot_detached', [ $role, $relationName, $pivotIds ] );
    }
}
