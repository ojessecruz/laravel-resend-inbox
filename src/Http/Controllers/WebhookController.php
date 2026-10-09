<?php

declare(strict_types=1);

namespace Jessecruz\LaravelResendInbox\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Jessecruz\LaravelResendInbox\Jobs\ProcessResendWebhook;
use Jessecruz\LaravelResendInbox\Support\InboxConfig;
use Jessecruz\ResendInbox\Exceptions\InvalidWebhook;
use Jessecruz\ResendInbox\Webhook\UnhandledEvent;
use Jessecruz\ResendInbox\Webhook\WebhookParser;
use Symfony\Component\HttpFoundation\Response;

/**
 * Receives Resend webhooks. The Svix signature is verified against
 * inbox.resend.webhook_secret; the event itself is processed on the queue.
 */
final class WebhookController
{
    public function __invoke(Request $request): JsonResponse
    {
        $secret = InboxConfig::string('inbox.resend.webhook_secret');

        abort_if($secret === '', Response::HTTP_SERVICE_UNAVAILABLE);

        try {
            $event = (new WebhookParser($secret))->parse($request->getContent(), $request->headers->all());
        } catch (InvalidWebhook $e) {
            abort(str_contains($e->getMessage(), 'signature') ? Response::HTTP_UNAUTHORIZED : Response::HTTP_BAD_REQUEST);
        }

        if (! $event instanceof UnhandledEvent) {
            ProcessResendWebhook::dispatch($event);
        }

        return response()->json(['ok' => true]);
    }
}
