<?php

declare(strict_types=1);

namespace Jessecruz\LaravelResendInbox\Actions;

use Jessecruz\LaravelResendInbox\Events\InboxEmailDeliveryFailed;
use Jessecruz\LaravelResendInbox\Models\InboxMessage;
use Jessecruz\ResendInbox\Direction;
use Jessecruz\ResendInbox\EmailAddress;
use Jessecruz\ResendInbox\Webhook\DeliveryStatusChanged;

final class RecordDeliveryStatus
{
    /**
     * Apply a Resend delivery webhook to an email sent from the inbox. Emails
     * Resend sent for anything else (transactional mail, broadcasts) are
     * ignored. The status only moves forward, so late or repeated webhooks
     * neither regress it nor notify twice. The Message-ID Resend reports
     * replaces ours in case the provider rewrote it, so replies still
     * thread. Returns whether the email belongs to the inbox.
     */
    public function handle(DeliveryStatusChanged $event): bool
    {
        $message = InboxMessage::query()
            ->where('resend_id', $event->emailId)
            ->where('direction', Direction::Outbound)
            ->first();

        if ($message === null) {
            return false;
        }

        $reportedMessageId = EmailAddress::messageId($event->messageId);

        if ($reportedMessageId !== null && $reportedMessageId !== $message->message_id) {
            $message->message_id = $reportedMessageId;
        }

        $advances = $event->status->advancesFrom($message->delivery_status);

        if ($advances) {
            $message->delivery_status = $event->status;
            $message->bounce_message = $event->bounceMessage;
        }

        $message->save();

        if ($advances && $event->status->isFailure()) {
            InboxEmailDeliveryFailed::dispatch($message);
        }

        return true;
    }
}
