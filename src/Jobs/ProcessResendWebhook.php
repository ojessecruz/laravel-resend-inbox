<?php

declare(strict_types=1);

namespace Jessecruz\LaravelResendInbox\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Jessecruz\LaravelResendInbox\Actions\RecordDeliveryStatus;
use Jessecruz\LaravelResendInbox\Actions\StoreReceivedEmail;
use Jessecruz\ResendInbox\Webhook\DeliveryStatusChanged;
use Jessecruz\ResendInbox\Webhook\EmailReceived;
use Jessecruz\ResendInbox\Webhook\WebhookEvent;

/**
 * Handles a verified Resend webhook event off the request cycle. Every
 * branch is idempotent (Resend redelivers on failure), and the job retries
 * with backoff while the Resend API is unreachable.
 */
final class ProcessResendWebhook implements ShouldQueue
{
    use Queueable;

    public int $tries = 6;

    public function __construct(public WebhookEvent $event)
    {
        $this->onConnection(config('inbox.queue.connection'));
        $this->onQueue(config('inbox.queue.name'));
    }

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [30, 60, 300, 900, 3600];
    }

    public function handle(StoreReceivedEmail $storeReceivedEmail, RecordDeliveryStatus $recordDeliveryStatus): void
    {
        if ($this->event instanceof EmailReceived) {
            $storeReceivedEmail->handle($this->event->emailId);

            return;
        }

        if ($this->event instanceof DeliveryStatusChanged) {
            $recorded = $recordDeliveryStatus->handle($this->event);

            // A bounce for a suppressed address can beat SendEmail's insert;
            // look once more before treating the email as not ours.
            if (! $recorded && $this->event->status->isFailure() && $this->attempts() < 2) {
                $this->release(60);
            }
        }
    }
}
