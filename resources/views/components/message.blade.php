{{-- One email of the conversation. Props: message (InboxMessage), allowImages. --}}
@props(['message', 'allowImages' => false])

@php
    $hasRemoteImages = filled($message->html)
        && preg_match('/(src|background)\s*=\s*["\']?\s*https?:|url\(\s*["\']?https?:/i', $message->html) === 1;
@endphp

<article {{ $attributes->class([
    'rounded-2xl border p-5',
    'border-zinc-200 dark:border-zinc-800' => $message->isInbound(),
    'border-blue-200 bg-blue-50/40 dark:border-blue-900 dark:bg-blue-950/20' => ! $message->isInbound(),
]) }}>
    <header class="flex flex-wrap items-start justify-between gap-3">
        <div class="min-w-0 space-y-0.5 text-sm">
            <p class="break-all font-semibold text-zinc-900 dark:text-zinc-100">{{ $message->fromLabel() }}</p>
            <p class="break-all text-zinc-600 dark:text-zinc-400">{{ __('inbox::inbox.message.to', ['address' => implode(', ', $message->to)]) }}</p>
            @if (! empty($message->cc))
                <p class="break-all text-zinc-600 dark:text-zinc-400">{{ __('inbox::inbox.message.cc', ['address' => implode(', ', $message->cc)]) }}</p>
            @endif
            @if (! $message->isInbound() && $message->sender)
                <p class="text-zinc-600 dark:text-zinc-400">{{ __('inbox::inbox.message.sent_by', ['name' => $message->sender->getAttribute('name')]) }}</p>
            @endif
        </div>

        <div class="flex items-center gap-2">
            <x-inbox::delivery-status :message="$message" />
            <time class="text-xs text-zinc-500" datetime="{{ $message->sent_at->toIso8601String() }}" title="{{ $message->sent_at->toDayDateTimeString() }}">
                {{ $message->sent_at->diffForHumans() }}
            </time>
        </div>
    </header>

    <div class="mt-4">
        @if (filled($message->html))
            @if ($hasRemoteImages && ! $allowImages)
                <div class="mb-3 flex items-center gap-3 text-sm text-zinc-600 dark:text-zinc-400">
                    {{ __('inbox::inbox.message.images_blocked') }}
                    <x-inbox::button size="sm" wire:click="loadImages({{ $message->id }})">{{ __('inbox::inbox.actions.load_images') }}</x-inbox::button>
                </div>
            @endif

            <x-inbox::email-body :html="$message->html" :allow-images="$allowImages" />
        @else
            <div class="whitespace-pre-wrap break-words text-sm text-zinc-800 dark:text-zinc-200">{{ $message->text }}</div>
        @endif
    </div>

    @if (! empty($message->attachments))
        <div class="mt-4 flex flex-wrap gap-2">
            @foreach ($message->attachments as $attachment)
                <x-inbox::button size="sm" :href="route('inbox.attachments.download', ['message' => $message, 'attachment' => $attachment['id']])" target="_blank" rel="noopener">
                    {{ $attachment['filename'] ?? __('inbox::inbox.message.attachment') }}
                    @if (! empty($attachment['size']))
                        <span class="text-zinc-500">({{ \Illuminate\Support\Number::fileSize($attachment['size']) }})</span>
                    @endif
                </x-inbox::button>
            @endforeach
        </div>
    @endif
</article>
