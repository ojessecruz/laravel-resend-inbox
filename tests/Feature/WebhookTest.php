<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Jessecruz\LaravelResendInbox\Events\InboxEmailDeliveryFailed;
use Jessecruz\LaravelResendInbox\Events\InboxEmailReceived;
use Jessecruz\LaravelResendInbox\Models\InboxMessage;
use Jessecruz\ResendInbox\DeliveryStatus;

it('rejects webhooks with a forged or missing signature', function () {
    $payload = ['type' => 'email.received', 'data' => ['email_id' => 'rcv_1']];
    $body = json_encode($payload, JSON_THROW_ON_ERROR);

    postResendWebhook($payload, [...resendWebhookHeaders($body), 'svix-signature' => 'v1,'.base64_encode('forged')])->assertUnauthorized();
    postResendWebhook($payload, [])->assertUnauthorized();

    expect(InboxMessage::query()->count())->toBe(0);
});

it('rejects a signed body that is not an event', function () {
    $body = '{"data":{}}';
    $server = ['CONTENT_TYPE' => 'application/json'];

    foreach (resendWebhookHeaders($body) as $key => $value) {
        $server['HTTP_'.strtoupper(str_replace('-', '_', $key))] = $value;
    }

    $this->call('POST', route('inbox.webhook'), [], [], [], $server, $body)->assertBadRequest();
});

it('refuses webhooks while no signing secret is configured', function () {
    config()->set('inbox.resend.webhook_secret', null);

    postResendWebhook(['type' => 'email.received', 'data' => ['email_id' => 'rcv_1']])->assertServiceUnavailable();
});

it('stores an inbound email once and dispatches the event once', function () {
    fakeResend()->receiveEmail(receivedEmail());
    Event::fake([InboxEmailReceived::class]);

    $payload = ['type' => 'email.received', 'data' => ['email_id' => 'rcv_1']];

    postResendWebhook($payload)->assertSuccessful();
    postResendWebhook($payload)->assertSuccessful();

    $message = InboxMessage::query()->sole();

    expect($message->mailbox)->toBe('contato@elenya.app')
        ->and($message->from_address)->toBe('maria@acme.com')
        ->and($message->from_name)->toBe('Maria Silva')
        ->and($message->message_id)->toBe('abc-123@mail.acme.com')
        ->and($message->attachments[0]['filename'] ?? null)->toBe('proposta.pdf')
        ->and($message->thread->mailbox)->toBe('contato@elenya.app')
        ->and($message->thread->isUnread())->toBeTrue();

    Event::assertDispatchedTimes(InboxEmailReceived::class, 1);
    Event::assertDispatched(InboxEmailReceived::class, fn (InboxEmailReceived $event): bool => $event->message->is($message));
});

it('ignores verified events the inbox does not handle', function () {
    $resend = fakeResend();

    postResendWebhook(['type' => 'contact.updated', 'data' => ['email' => 'ada@example.com']])->assertSuccessful();

    expect($resend->requests)->toBe([]);
});

it('moves the delivery status forward only and dispatches the failure once', function () {
    Event::fake([InboxEmailDeliveryFailed::class]);

    $message = InboxMessage::factory()->outbound()->create(['resend_id' => 'snd_1', 'message_id' => 'ours@elenya.app']);

    postResendWebhook(['type' => 'email.delivered', 'data' => ['email_id' => 'snd_1', 'message_id' => '<rewritten@resend.dev>']])->assertSuccessful();

    expect($message->refresh()->delivery_status)->toBe(DeliveryStatus::Delivered)
        ->and($message->message_id)->toBe('rewritten@resend.dev');

    $bounce = ['type' => 'email.bounced', 'data' => ['email_id' => 'snd_1', 'bounce' => ['message' => 'Mailbox does not exist']]];

    postResendWebhook($bounce)->assertSuccessful();
    postResendWebhook($bounce)->assertSuccessful();
    postResendWebhook(['type' => 'email.sent', 'data' => ['email_id' => 'snd_1']])->assertSuccessful();

    expect($message->refresh()->delivery_status)->toBe(DeliveryStatus::Bounced)
        ->and($message->bounce_message)->toBe('Mailbox does not exist');

    Event::assertDispatchedTimes(InboxEmailDeliveryFailed::class, 1);
});

it('ignores delivery webhooks for emails not sent from the inbox', function () {
    Event::fake([InboxEmailDeliveryFailed::class]);

    InboxMessage::factory()->create(['resend_id' => 'rcv_9']);

    postResendWebhook(['type' => 'email.delivered', 'data' => ['email_id' => 'transactional_1']])->assertSuccessful();
    postResendWebhook(['type' => 'email.bounced', 'data' => ['email_id' => 'rcv_9']])->assertSuccessful();

    expect(InboxMessage::query()->sole()->delivery_status)->toBeNull();
    Event::assertNotDispatched(InboxEmailDeliveryFailed::class);
});
