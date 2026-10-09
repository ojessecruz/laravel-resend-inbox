{{-- A panel around the conversation list and the reply form. Slot: header (optional bar on top). --}}
<div {{ $attributes->merge(['class' => 'overflow-hidden rounded-2xl border border-zinc-200 dark:border-zinc-800']) }}>
    @isset($header)
        <div {{ $header->attributes->merge(['class' => 'flex items-center gap-3 border-b border-zinc-200 bg-zinc-50 px-4 py-2 dark:border-zinc-800 dark:bg-zinc-900']) }}>
            {{ $header }}
        </div>
    @endisset

    {{ $slot }}
</div>
