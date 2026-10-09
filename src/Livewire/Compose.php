<?php

declare(strict_types=1);

namespace Jessecruz\LaravelResendInbox\Livewire;

use Illuminate\Contracts\View\View;
use Jessecruz\LaravelResendInbox\Actions\SendEmail;
use Jessecruz\LaravelResendInbox\Livewire\Concerns\AuthorizesInbox;
use Jessecruz\LaravelResendInbox\Livewire\Forms\EmailForm;
use Jessecruz\LaravelResendInbox\Support\InboxConfig;
use Livewire\Attributes\Computed;
use Livewire\Component;

final class Compose extends Component
{
    use AuthorizesInbox;

    public EmailForm $form;

    public function mount(): void
    {
        $this->form->from = $this->senders()[0] ?? '';
    }

    /**
     * @return list<string>
     */
    #[Computed]
    public function senders(): array
    {
        return InboxConfig::mailbox()->allowedSenders();
    }

    public function send(SendEmail $action): void
    {
        $message = $this->form->send($action);

        if ($message !== null) {
            $this->redirectRoute('inbox.threads.show', ['thread' => $message->inbox_thread_id]);
        }
    }

    public function render(): View
    {
        return view('inbox::livewire.compose')
            ->layout(InboxConfig::string('inbox.layout'), ['title' => __('inbox::inbox.titles.compose')]);
    }
}
