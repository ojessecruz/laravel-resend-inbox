{{-- One tab per mailbox. Props: tabs (key => label), current, counts (key => unread). Binds wire:model "tab". --}}
@props(['tabs', 'current', 'counts' => []])

<nav {{ $attributes->merge(['class' => 'flex flex-wrap gap-1 border-b border-zinc-200 dark:border-zinc-800']) }} aria-label="{{ __('inbox::inbox.tabs.label') }}">
    @foreach ($tabs as $key => $label)
        <button
            type="button"
            wire:click="$set('tab', @js((string) $key))"
            wire:key="tab-{{ $key === '' ? 'all' : $key }}"
            @class([
                '-mb-px inline-flex items-center gap-2 border-b-2 px-3 py-2 text-sm font-medium',
                'border-zinc-900 text-zinc-900 dark:border-white dark:text-white' => $current === (string) $key,
                'border-transparent text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200' => $current !== (string) $key,
            ])
            @if ($current === (string) $key) aria-current="page" @endif
        >
            {{ $label }}
            @if (($counts[$key] ?? 0) > 0)
                <x-inbox::badge tone="info">{{ $counts[$key] }}</x-inbox::badge>
            @endif
        </button>
    @endforeach
</nav>
