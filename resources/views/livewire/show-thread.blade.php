<div class="max-w-4xl space-y-8">
    <div class="space-y-2">
        <a href="{{ route('inbox.index') }}" wire:navigate class="text-sm text-zinc-600 hover:underline dark:text-zinc-400">&larr; {{ __('inbox::inbox.titles.inbox') }}</a>

        <x-inbox::heading :title="$thread->subject !== '' ? $thread->subject : __('inbox::inbox.no_subject')" :subtitle="$thread->mailbox">
            <x-slot:actions>
                <x-inbox::button wire:click="markUnread">{{ __('inbox::inbox.actions.mark_unread') }}</x-inbox::button>
                @if ($thread->isArchived())
                    <x-inbox::button wire:click="unarchive">{{ __('inbox::inbox.actions.unarchive') }}</x-inbox::button>
                @else
                    <x-inbox::button wire:click="archive">{{ __('inbox::inbox.actions.archive') }}</x-inbox::button>
                @endif
            </x-slot:actions>
        </x-inbox::heading>

        @if ($thread->isArchived())
            <x-inbox::badge>{{ __('inbox::inbox.status.archived') }}</x-inbox::badge>
        @endif
    </div>

    <div class="space-y-4">
        @foreach ($this->messages as $message)
            @php($allowImages = in_array($message->id, $imagesLoaded, true))
            <x-inbox::message :message="$message" :allow-images="$allowImages" wire:key="message-{{ $message->id }}-{{ $allowImages ? 'images' : 'blocked' }}" />
        @endforeach
    </div>

    <x-inbox::card>
        <form wire:submit="send" class="space-y-4 p-5">
            <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">{{ __('inbox::inbox.titles.reply') }}</h2>

            <x-inbox::notice :message="$notice" />

            <x-inbox::email-form form="reply" :senders="$this->senders" />

            <div class="flex justify-end">
                <x-inbox::button type="submit" variant="primary" wire:loading.attr="disabled">{{ __('inbox::inbox.actions.send_reply') }}</x-inbox::button>
            </div>
        </form>
    </x-inbox::card>
</div>
