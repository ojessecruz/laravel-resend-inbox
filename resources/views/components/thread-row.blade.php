{{-- One conversation in the list. Props: thread (InboxThread with latestMessage and messages_count). --}}
@props(['thread'])

@php($latest = $thread->latestMessage)

<li {{ $attributes->merge(['class' => 'flex items-start gap-3 px-4 py-3 hover:bg-zinc-50 dark:hover:bg-zinc-900']) }}>
    <x-inbox::checkbox wire:model.live="selected" value="{{ $thread->id }}" class="mt-1" :aria-label="__('inbox::inbox.actions.select')" />

    <a href="{{ route('inbox.threads.show', $thread) }}" wire:navigate class="min-w-0 flex-1">
        <div class="flex items-baseline justify-between gap-3">
            <p @class(['truncate text-sm', 'font-semibold text-zinc-900 dark:text-zinc-100' => $thread->isUnread(), 'text-zinc-700 dark:text-zinc-300' => ! $thread->isUnread()])>
                {{ $latest?->isInbound() ? $latest->fromLabel() : __('inbox::inbox.list.to', ['address' => implode(', ', $latest->to ?? [])]) }}
                @if ($thread->messages_count > 1)
                    <span class="font-normal text-zinc-500">({{ $thread->messages_count }})</span>
                @endif
            </p>
            <time class="shrink-0 text-xs text-zinc-500" datetime="{{ $thread->last_message_at->toIso8601String() }}" title="{{ $thread->last_message_at->toDayDateTimeString() }}">
                {{ $thread->last_message_at->diffForHumans() }}
            </time>
        </div>
        <p @class(['truncate text-sm', 'font-medium text-zinc-900 dark:text-zinc-100' => $thread->isUnread(), 'text-zinc-600 dark:text-zinc-400' => ! $thread->isUnread()])>
            {{ $thread->subject !== '' ? $thread->subject : __('inbox::inbox.no_subject') }}
        </p>
        <p class="truncate text-xs text-zinc-500">{{ $thread->mailbox }}</p>
    </a>
</li>
