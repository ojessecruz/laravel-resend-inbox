<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Gate;
use Jessecruz\LaravelResendInbox\Livewire\Compose;
use Jessecruz\LaravelResendInbox\Livewire\Inbox;
use Jessecruz\LaravelResendInbox\Livewire\ShowThread;
use Jessecruz\LaravelResendInbox\Models\InboxMessage;
use Jessecruz\LaravelResendInbox\Models\InboxThread;
use Jessecruz\LaravelResendInbox\ResendInboxServiceProvider;
use Jessecruz\LaravelResendInbox\Tests\Stubs\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->resend = fakeResend();
});

function customer(): User
{
    return User::query()->create(['name' => 'Cliente', 'email' => 'cliente@example.com']);
}

test('only users passing the viewInbox gate open the inbox pages', function (Closure $url) {
    $thread = InboxMessage::factory()->create()->thread;

    $this->get($url($thread))->assertRedirect();

    $this->actingAs(customer())->get($url($thread))->assertForbidden();

    $this->actingAs(admin())->get($url($thread))->assertSuccessful();
})->with([
    'inbox' => [fn (InboxThread $thread): string => route('inbox.index')],
    'conversation' => [fn (InboxThread $thread): string => route('inbox.threads.show', $thread)],
    'compose' => [fn (InboxThread $thread): string => route('inbox.compose')],
]);

test('livewire actions check the gate too, not only the page route', function () {
    $thread = InboxMessage::factory()->create()->thread;

    Livewire::actingAs(customer())->test(ShowThread::class, ['thread' => $thread])->assertForbidden();
    Livewire::actingAs(customer())->test(Inbox::class)->assertForbidden();
    Livewire::actingAs(customer())->test(Compose::class)->assertForbidden();
});

test('the default gate keeps the inbox closed outside local', function () {
    $gate = app(Illuminate\Contracts\Auth\Access\Gate::class);
    (fn () => $this->abilities = [])->call($gate);

    $this->app->getProvider(ResendInboxServiceProvider::class)->packageBooted();

    expect(Gate::forUser(admin())->allows('viewInbox'))->toBeFalse();

    $this->app['env'] = 'local';

    expect(Gate::forUser(admin())->allows('viewInbox'))->toBeTrue();
});

test('the inbox lists conversations by tab, with others for unconfigured addresses', function () {
    $contato = InboxMessage::factory()->create(['subject' => 'Agenda não carrega', 'mailbox' => 'contato@elenya.app'])->thread;
    $contato->update(['mailbox' => 'contato@elenya.app', 'subject' => 'Agenda não carrega']);
    $financeiro = InboxMessage::factory()->create(['mailbox' => 'financeiro@elenya.app'])->thread;
    $financeiro->update(['mailbox' => 'financeiro@elenya.app', 'subject' => 'Nota fiscal', 'read_at' => now()]);
    $outros = InboxMessage::factory()->create(['mailbox' => 'jesse@elenya.app'])->thread;
    $outros->update(['mailbox' => 'jesse@elenya.app', 'subject' => 'Parceria']);
    $arquivada = InboxMessage::factory()->create()->thread;
    $arquivada->update(['mailbox' => 'contato@elenya.app', 'subject' => 'Antiga', 'archived_at' => now()]);

    $component = Livewire::actingAs(admin())->test(Inbox::class)
        ->assertSee(['Agenda não carrega', 'Nota fiscal', 'Parceria', 'suporte@elenya.app', 'Others'])
        ->assertDontSee('Antiga');

    expect($component->get('unreadByTab'))->toBe([
        '' => 2,
        'contato@elenya.app' => 1,
        'suporte@elenya.app' => 0,
        'financeiro@elenya.app' => 0,
        'others' => 1,
    ]);

    $component->set('tab', 'financeiro@elenya.app')
        ->assertSee('Nota fiscal')
        ->assertDontSee(['Agenda não carrega', 'Parceria']);

    $component->set('tab', Inbox::OTHERS)
        ->assertSee('Parceria')
        ->assertDontSee(['Agenda não carrega', 'Nota fiscal']);

    $component->set('tab', '')->set('status', 'unread')
        ->assertSee(['Agenda não carrega', 'Parceria'])
        ->assertDontSee('Nota fiscal');

    $component->set('status', 'archived')
        ->assertSee('Antiga')
        ->assertDontSee('Agenda não carrega');

    $component->set('status', 'inbox')->set('search', 'maria-nao-existe')
        ->assertDontSee(['Agenda não carrega', 'Nota fiscal', 'Parceria']);
});

test('conversations can be archived and restored in bulk', function () {
    $threads = InboxThread::factory()->count(3)->create();

    Livewire::actingAs(admin())->test(Inbox::class)
        ->set('selected', [(string) $threads[0]->id, (string) $threads[1]->id])
        ->call('archiveSelected')
        ->assertSet('selected', [])
        ->assertSee('2 conversations archived.');

    expect($threads[0]->refresh()->isArchived())->toBeTrue()
        ->and($threads[1]->refresh()->isArchived())->toBeTrue()
        ->and($threads[2]->refresh()->isArchived())->toBeFalse();

    Livewire::actingAs(admin())->test(Inbox::class)
        ->set('status', 'archived')
        ->set('selectPage', true)
        ->assertSet('selected', fn (array $selected): bool => count($selected) === 2)
        ->call('unarchiveSelected');

    expect(InboxThread::query()->whereNotNull('archived_at')->count())->toBe(0);
});

test('changing a filter clears the selection', function () {
    $thread = InboxThread::factory()->create();

    Livewire::actingAs(admin())->test(Inbox::class)
        ->set('selected', [(string) $thread->id])
        ->set('tab', 'suporte@elenya.app')
        ->assertSet('selected', []);
});

test('opening a conversation marks it read and prefills the reply', function () {
    $message = InboxMessage::factory()->create([
        'mailbox' => 'suporte@elenya.app',
        'from_address' => 'maria@acme.com',
        'reply_to' => ['respostas@acme.com'],
        'subject' => 'Agenda não carrega',
    ]);
    $message->thread->update(['subject' => 'Agenda não carrega']);

    Livewire::actingAs(admin())->test(ShowThread::class, ['thread' => $message->thread])
        ->assertSet('reply.from', 'suporte@elenya.app')
        ->assertSet('reply.to', 'respostas@acme.com')
        ->assertSet('reply.subject', 'Re: Agenda não carrega')
        ->assertSee('maria@acme.com');

    expect($message->thread->refresh()->isUnread())->toBeFalse();
});

test('the reply goes out from the conversation', function () {
    $message = InboxMessage::factory()->create(['mailbox' => 'contato@elenya.app', 'from_address' => 'maria@acme.com']);

    Livewire::actingAs(admin())->test(ShowThread::class, ['thread' => $message->thread])
        ->set('reply.body', 'Resolvido!')
        ->call('send')
        ->assertHasNoErrors()
        ->assertSet('reply.body', '')
        ->assertSee('Reply sent.');

    expect($this->resend->sentEmails()[0]['to'])->toBe(['maria@acme.com'])
        ->and($message->thread->messages()->count())->toBe(2);
});

test('reply validation rejects invalid recipients and a forged sender', function () {
    $thread = InboxMessage::factory()->create(['mailbox' => 'contato@elenya.app'])->thread;

    Livewire::actingAs(admin())->test(ShowThread::class, ['thread' => $thread])
        ->set('reply.to', 'maria@acme.com, not-an-email')
        ->set('reply.body', 'Oi')
        ->call('send')
        ->assertHasErrors(['reply.to']);

    Livewire::actingAs(admin())->test(ShowThread::class, ['thread' => $thread])
        ->set('reply.from', 'ceo@elenya.app')
        ->set('reply.body', 'Oi')
        ->call('send')
        ->assertHasErrors(['reply.from']);

    expect($this->resend->sentEmails())->toBe([]);
});

test('a failed send keeps the message in the form with an error', function () {
    $this->resend->failSends = true;
    $thread = InboxMessage::factory()->create(['mailbox' => 'contato@elenya.app'])->thread;

    Livewire::actingAs(admin())->test(ShowThread::class, ['thread' => $thread])
        ->set('reply.body', 'Oi')
        ->call('send')
        ->assertHasErrors(['reply.body'])
        ->assertSet('reply.body', 'Oi');
});

test('a conversation can be archived, restored and marked unread', function () {
    $thread = InboxMessage::factory()->create()->thread;

    Livewire::actingAs(admin())->test(ShowThread::class, ['thread' => $thread])
        ->call('archive')
        ->assertRedirect(route('inbox.index'));
    expect($thread->refresh()->isArchived())->toBeTrue();

    Livewire::actingAs(admin())->test(ShowThread::class, ['thread' => $thread])->call('unarchive');
    expect($thread->refresh()->isArchived())->toBeFalse();

    Livewire::actingAs(admin())->test(ShowThread::class, ['thread' => $thread])
        ->call('markUnread')
        ->assertRedirect(route('inbox.index'));
    expect($thread->refresh()->isUnread())->toBeTrue();
});

test('remote images stay blocked until loaded', function () {
    $message = InboxMessage::factory()->create(['html' => '<p>Oi</p><img src="https://tracker.example/pixel.gif">']);

    Livewire::actingAs(admin())->test(ShowThread::class, ['thread' => $message->thread])
        ->assertSee('Remote images are blocked.')
        ->assertSeeHtml('img-src data:;')
        ->call('loadImages', $message->id)
        ->assertDontSee('Remote images are blocked.')
        ->assertSeeHtml('img-src https: http: data:');
});

test('composing a new email redirects to its conversation', function () {
    $component = Livewire::actingAs(admin())->test(Compose::class)
        ->assertSet('form.from', 'contato@elenya.app')
        ->set('form.from', 'financeiro@elenya.app')
        ->set('form.to', 'maria@acme.com')
        ->set('form.subject', 'Sua nota fiscal')
        ->set('form.body', 'Segue a nota.')
        ->call('send');

    $thread = InboxThread::query()->sole();

    $component->assertRedirect(route('inbox.threads.show', $thread));

    expect($thread->mailbox)->toBe('financeiro@elenya.app')
        ->and($this->resend->sentEmails()[0]['from'])->toBe('"Jessé do Elenya" <financeiro@elenya.app>');
});

test('attachments redirect to the resend download url', function () {
    $message = InboxMessage::factory()->create([
        'resend_id' => 'rcv_1',
        'attachments' => [['id' => 'att_1', 'filename' => 'proposta.pdf', 'content_type' => 'application/pdf', 'size' => 2048]],
    ]);
    $this->resend->attachment('rcv_1', 'att_1', 'https://resend.example/att_1');

    $url = route('inbox.attachments.download', ['message' => $message, 'attachment' => 'att_1']);

    $this->actingAs(customer())->get($url)->assertForbidden();
    $this->actingAs(admin())->get($url)->assertRedirect('https://resend.example/att_1');
    $this->actingAs(admin())->get(route('inbox.attachments.download', ['message' => $message, 'attachment' => 'att_other']))->assertNotFound();
});

test('screens are translated', function () {
    app()->setLocale('pt_BR');

    InboxMessage::factory()->create(['mailbox' => 'jesse@elenya.app'])->thread->update(['mailbox' => 'jesse@elenya.app']);

    Livewire::actingAs(admin())->test(Inbox::class)->assertSee(['Caixa de entrada', 'Outros', 'Novo e-mail']);
});
