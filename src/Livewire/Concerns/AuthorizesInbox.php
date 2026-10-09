<?php

declare(strict_types=1);

namespace Jessecruz\LaravelResendInbox\Livewire\Concerns;

use Illuminate\Support\Facades\Gate;

/**
 * Checks the "viewInbox" gate on every Livewire request. Actions arrive on
 * Livewire's own update route, which skips the page route's middleware, so
 * the route middleware alone does not protect them.
 */
trait AuthorizesInbox
{
    public function bootAuthorizesInbox(): void
    {
        Gate::authorize('viewInbox');
    }
}
