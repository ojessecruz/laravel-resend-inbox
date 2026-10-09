<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Jessecruz\LaravelResendInbox\Actions\StoreReceivedEmail;
use Jessecruz\LaravelResendInbox\Events\InboxEmailReceived;
use Jessecruz\LaravelResendInbox\Models\InboxMessage;

beforeEach(function () {
    $this->resend = fakeResend();
});

it('files a reply into the conversation it references and reopens it', function () {
    Event::fake([InboxEmailReceived::class]);

    $original = InboxMessage::factory()->outbound()->create(['message_id' => 'ours-1@elenya.app', 'to' => ['maria@acme.com']]);
    $original->thread->update(['read_at' => now(), 'archived_at' => now()]);

    $this->resend->receiveEmail(receivedEmail([
        'subject' => 'Something unrelated',
        'headers' => ['in-reply-to' => '<ours-1@elenya.app>', 'references' => '<ours-1@elenya.app>'],
    ]));

    $message = app(StoreReceivedEmail::class)->handle('rcv_1');

    expect($message?->inbox_thread_id)->toBe($original->inbox_thread_id)
        ->and($message?->in_reply_to)->toBe('ours-1@elenya.app')
        ->and($message?->thread->read_at)->toBeNull()
        ->and($message?->thread->archived_at)->toBeNull();
});

it('threads by subject only with the same correspondent when headers are missing', function () {
    Event::fake([InboxEmailReceived::class]);

    $withMaria = InboxMessage::factory()->outbound()->create(['subject' => 'Preço para 50 profissionais', 'to' => ['maria@acme.com']]);
    $withMaria->thread->update(['subject' => 'Preço para 50 profissionais']);
    $withJohn = InboxMessage::factory()->outbound()->create(['subject' => 'Preço para 50 profissionais', 'to' => ['john@other.com']]);
    $withJohn->thread->update(['subject' => 'Preço para 50 profissionais']);

    $this->resend
        ->receiveEmail(receivedEmail(['id' => 'rcv_maria', 'subject' => 'RES: Preço para 50 profissionais']))
        ->receiveEmail(receivedEmail(['id' => 'rcv_eve', 'from' => 'eve@evil.com', 'subject' => 'Re: Preço para 50 profissionais']));

    expect(app(StoreReceivedEmail::class)->handle('rcv_maria')?->inbox_thread_id)->toBe($withMaria->inbox_thread_id)
        ->and(app(StoreReceivedEmail::class)->handle('rcv_eve')?->inbox_thread_id)->not->toBeIn([$withMaria->inbox_thread_id, $withJohn->inbox_thread_id]);
});

it('stores auto-replies and bounces without dispatching the event', function (array $overrides) {
    Event::fake([InboxEmailReceived::class]);

    $this->resend->receiveEmail(receivedEmail($overrides));

    app(StoreReceivedEmail::class)->handle('rcv_1');

    expect(InboxMessage::query()->count())->toBe(1);
    Event::assertNotDispatched(InboxEmailReceived::class);
})->with([
    'auto-submitted' => [['headers' => ['Auto-Submitted' => 'auto-replied']]],
    'bulk precedence' => [['headers' => ['Precedence' => 'bulk']]],
    'mailer daemon' => [['from' => 'MAILER-DAEMON@mx.acme.com']],
]);

it('keeps mail for an address that is not configured and dispatches the event', function () {
    Event::fake([InboxEmailReceived::class]);

    $this->resend->receiveEmail(receivedEmail(['to' => ['jesse@elenya.app'], 'received_for' => ['jesse@elenya.app']]));

    $message = app(StoreReceivedEmail::class)->handle('rcv_1');

    expect($message?->mailbox)->toBe('jesse@elenya.app')
        ->and($message?->thread->mailbox)->toBe('jesse@elenya.app');
    Event::assertDispatchedTimes(InboxEmailReceived::class, 1);
});

it('drops inbound mail addressed to other domains on the account', function () {
    Event::fake([InboxEmailReceived::class]);

    $this->resend->receiveEmail(receivedEmail([
        'to' => ['Support <support@other-product.io>'],
        'cc' => ['ops@other-product.io'],
        'received_for' => ['support@other-product.io'],
    ]));

    expect(app(StoreReceivedEmail::class)->handle('rcv_1'))->toBeNull()
        ->and(InboxMessage::query()->count())->toBe(0);
    Event::assertNotDispatched(InboxEmailReceived::class);
});

it('keeps mail where only the envelope or a cc is on our domain', function (array $overrides) {
    $this->resend->receiveEmail(receivedEmail($overrides));

    expect(app(StoreReceivedEmail::class)->handle('rcv_1')?->mailbox)->toBe('suporte@elenya.app');
})->with([
    'envelope only' => [['to' => ['team@other-product.io'], 'received_for' => ['suporte@elenya.app']]],
    'cc only' => [['to' => ['team@other-product.io'], 'cc' => ['Suporte <Suporte@Elenya.App>'], 'received_for' => []]],
]);

it('moves the conversation to the address that received the latest email', function () {
    $original = InboxMessage::factory()->outbound()->create([
        'message_id' => 'ours-1@elenya.app',
        'mailbox' => 'contato@elenya.app',
        'from_address' => 'contato@elenya.app',
        'to' => ['maria@acme.com'],
    ]);

    $this->resend->receiveEmail(receivedEmail([
        'to' => ['financeiro@elenya.app'],
        'received_for' => ['financeiro@elenya.app'],
        'headers' => ['In-Reply-To' => '<ours-1@elenya.app>'],
    ]));

    app(StoreReceivedEmail::class)->handle('rcv_1');

    expect($original->thread->refresh()->mailbox)->toBe('financeiro@elenya.app');
});
