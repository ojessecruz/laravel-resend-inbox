<?php

declare(strict_types=1);

use Jessecruz\LaravelResendInbox\Actions\SendEmail;
use Jessecruz\LaravelResendInbox\Models\InboxMessage;
use Jessecruz\LaravelResendInbox\Models\InboxThread;
use Jessecruz\ResendInbox\DeliveryStatus;
use Jessecruz\ResendInbox\Exceptions\DisallowedSender;
use Jessecruz\ResendInbox\Exceptions\MailboxException;

beforeEach(function () {
    $this->resend = fakeResend();
});

it('sends a new email from a configured address and opens a read conversation', function () {
    $author = admin();

    $message = app(SendEmail::class)->handle($author, 'contato@elenya.app', ['maria@acme.com'], 'Olá', 'Oi **Maria**');

    $sent = $this->resend->sentEmails()[0];

    expect($sent['from'])->toBe('"Jessé do Elenya" <contato@elenya.app>')
        ->and($sent['to'])->toBe(['maria@acme.com'])
        ->and($sent['subject'])->toBe('Olá')
        ->and($sent['html'])->toContain('<strong>Maria</strong>')
        ->and($sent['text'])->toBe('Oi **Maria**')
        ->and($sent['headers']['Message-ID'])->toMatch('/^<[0-9a-f-]+@elenya\.app>$/')
        ->and($sent['headers'])->not->toHaveKey('In-Reply-To')
        ->and($message->resend_id)->toBe('snd_1')
        ->and($message->delivery_status)->toBe(DeliveryStatus::Sent)
        ->and($message->sent_by)->toBe($author->id)
        ->and($message->sender?->is($author))->toBeTrue()
        ->and($message->thread->subject)->toBe('Olá')
        ->and($message->thread->mailbox)->toBe('contato@elenya.app')
        ->and($message->thread->isUnread())->toBeFalse();
});

it('appends the signature of the sending address', function () {
    config()->set('inbox.signatures', ['Suporte@Elenya.app' => "Jessé\nSuporte Elenya"]);

    app(SendEmail::class)->handle(null, 'suporte@elenya.app', ['maria@acme.com'], 'Olá', "Oi, Maria\n\n");
    app(SendEmail::class)->handle(null, 'contato@elenya.app', ['maria@acme.com'], 'Olá', 'Oi, Maria');

    expect($this->resend->sentEmails()[0]['text'])->toBe("Oi, Maria\n\nJessé\nSuporte Elenya")
        ->and($this->resend->sentEmails()[1]['text'])->toBe('Oi, Maria');
});

it('threads a reply with in-reply-to and references, from the address that received it', function () {
    $thread = InboxThread::factory()->create();
    InboxMessage::factory()->for($thread, 'thread')->outbound()->create(['message_id' => 'first@elenya.app', 'sent_at' => now()->subHour()]);
    InboxMessage::factory()->for($thread, 'thread')->create(['message_id' => 'second@acme.com', 'mailbox' => 'jesse@elenya.app', 'sent_at' => now()]);

    $message = app(SendEmail::class)->handle(admin(), 'jesse@elenya.app', ['maria@acme.com'], 'Re: Oi', 'Obrigado', $thread);

    $sent = $this->resend->sentEmails()[0];

    expect($sent['from'])->toBe('"Jessé do Elenya" <jesse@elenya.app>')
        ->and($sent['headers']['In-Reply-To'])->toBe('<second@acme.com>')
        ->and($sent['headers']['References'])->toBe('<first@elenya.app> <second@acme.com>')
        ->and($message->inbox_thread_id)->toBe($thread->id)
        ->and($message->in_reply_to)->toBe('second@acme.com');
});

it('refuses a sender outside the allowed addresses', function () {
    try {
        app(SendEmail::class)->handle(admin(), 'ceo@elenya.app', ['maria@acme.com'], 'Oi', 'Corpo');
    } finally {
        expect($this->resend->sentEmails())->toBe([]);
    }
})->throws(DisallowedSender::class);

it('stores nothing when resend rejects the email', function () {
    $this->resend->failSends = true;

    expect(fn () => app(SendEmail::class)->handle(admin(), 'contato@elenya.app', ['maria@acme.com'], 'Oi', 'Corpo'))
        ->toThrow(MailboxException::class);

    expect(InboxThread::query()->count())->toBe(0)
        ->and(InboxMessage::query()->count())->toBe(0);
});
