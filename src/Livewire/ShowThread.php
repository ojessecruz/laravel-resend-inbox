<?php

declare(strict_types=1);

namespace Jessecruz\LaravelResendInbox\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Jessecruz\LaravelResendInbox\Actions\SendEmail;
use Jessecruz\LaravelResendInbox\Livewire\Concerns\AuthorizesInbox;
use Jessecruz\LaravelResendInbox\Livewire\Forms\EmailForm;
use Jessecruz\LaravelResendInbox\Models\InboxMessage;
use Jessecruz\LaravelResendInbox\Models\InboxThread;
use Jessecruz\LaravelResendInbox\Support\InboxConfig;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * @property-read Collection<int, InboxMessage> $messages
 * @property-read list<string> $senders
 */
final class ShowThread extends Component
{
    use AuthorizesInbox;

    public InboxThread $thread;

    public EmailForm $reply;

    /**
     * Messages whose remote images were allowed to load.
     *
     * @var list<int>
     */
    public array $imagesLoaded = [];

    public ?string $notice = null;

    public function mount(InboxThread $thread): void
    {
        $this->thread = $thread;

        if ($thread->isUnread()) {
            $thread->update(['read_at' => now()]);
        }

        $this->prefillReply();
    }

    /**
     * @return Collection<int, InboxMessage>
     */
    #[Computed]
    public function messages(): Collection
    {
        return $this->thread->messages()
            ->with('sender')
            ->oldest('sent_at')
            ->oldest('id')
            ->get();
    }

    /**
     * From options: configured addresses plus ours that received mail in
     * this conversation.
     *
     * @return list<string>
     */
    #[Computed]
    public function senders(): array
    {
        $received = $this->messages
            ->filter(fn (InboxMessage $message): bool => $message->isInbound())
            ->pluck('mailbox')
            ->map(strval(...))
            ->all();

        return InboxConfig::mailbox()->allowedSenders(array_values($received));
    }

    public function send(SendEmail $action): void
    {
        $message = $this->reply->send($action, $this->thread);

        if ($message === null) {
            return;
        }

        $this->thread->refresh();
        unset($this->messages, $this->senders);
        $this->prefillReply();

        $this->notice = __('inbox::inbox.notices.reply_sent');
    }

    public function loadImages(int $messageId): void
    {
        $this->imagesLoaded = array_values(array_unique([...$this->imagesLoaded, $messageId]));
    }

    public function markUnread(): void
    {
        $this->thread->update(['read_at' => null]);

        $this->redirectRoute('inbox.index');
    }

    public function archive(): void
    {
        $this->thread->update(['archived_at' => now()]);

        $this->redirectRoute('inbox.index');
    }

    public function unarchive(): void
    {
        $this->thread->update(['archived_at' => null]);
    }

    public function render(): View
    {
        return view('inbox::livewire.show-thread')
            ->layout(InboxConfig::string('inbox.layout'), ['title' => $this->thread->subject]);
    }

    /**
     * Answer the latest inbound message: to its Reply-To or sender, from our
     * address that received it, with a "Re:" subject.
     */
    private function prefillReply(): void
    {
        $messages = $this->messages;
        $lastInbound = $messages->last(fn (InboxMessage $message): bool => $message->isInbound());
        $last = $messages->last();

        $this->reply->reset();
        $this->reply->resetErrorBag();

        $this->reply->from = $lastInbound->mailbox ?? $last->from_address ?? (InboxConfig::mailboxes()[0] ?? '');
        $this->reply->to = $lastInbound?->replyAddress() ?? implode(', ', $last->to ?? []);
        $this->reply->subject = preg_match('/^\s*re\s*:/i', $this->thread->subject) === 1
            ? $this->thread->subject
            : 'Re: '.$this->thread->subject;
    }
}
