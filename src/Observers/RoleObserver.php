<?php

declare(strict_types=1);

namespace ArtisanPackUI\Rbac\Observers;

use ArtisanPackUI\Rbac\Concerns\HasRoles;
use ArtisanPackUI\Rbac\Models\Role;
use Illuminate\Support\Facades\Event;

/**
 * Role observer — emits package-level events when role records change.
 * Consumers (e.g. security-analytics) listen on the `rbac.role.*` channel
 * to layer on auditing. Pivot mutations are dispatched directly from
 * {@see HasRoles::assignRole()} and
 * {@see HasRoles::removeRole()} on the
 * `rbac.user.role_*` channel since Laravel does not fire pivot events.
 */
class RoleObserver
{
    public function created(Role $role): void
    {
        Event::dispatch('rbac.role.created', [$role]);
    }

    public function updated(Role $role): void
    {
        Event::dispatch('rbac.role.updated', [$role, $role->getChanges(), $role->getOriginal()]);
    }

    public function deleted(Role $role): void
    {
        Event::dispatch('rbac.role.deleted', [$role]);
    }
}
