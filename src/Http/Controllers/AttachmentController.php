<?php

declare(strict_types=1);

namespace Jessecruz\LaravelResendInbox\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Jessecruz\LaravelResendInbox\Models\InboxMessage;
use Jessecruz\ResendInbox\Exceptions\MailboxException;
use Jessecruz\ResendInbox\ResendMailbox;
use Symfony\Component\HttpFoundation\Response;

/**
 * Downloads an inbound attachment on demand: only its metadata is stored,
 * so the browser is redirected to Resend's short-lived download URL.
 */
final class AttachmentController
{
    public function __invoke(InboxMessage $message, string $attachment, ResendMailbox $resend): RedirectResponse
    {
        Gate::authorize('viewInbox');

        abort_unless($message->isInbound() && $message->resend_id !== null && $message->hasAttachment($attachment), Response::HTTP_NOT_FOUND);

        try {
            return redirect()->away($resend->receivedAttachmentUrl($message->resend_id, $attachment));
        } catch (MailboxException $e) {
            report($e);

            abort(Response::HTTP_BAD_GATEWAY, __('inbox::inbox.attachment_unavailable'));
        }
    }
}
