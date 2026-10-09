<?php

declare(strict_types=1);

namespace Jessecruz\LaravelResendInbox\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Jessecruz\LaravelResendInbox\Models\InboxMessage;

/**
 * An email sent from the inbox bounced or was marked as spam. Dispatched
 * once, when the status first becomes a failure.
 */
final class InboxEmailDeliveryFailed
{
    use Dispatchable;

    public function __construct(public InboxMessage $message) {}
}
