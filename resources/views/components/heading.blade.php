{{-- Page title with an optional subtitle and actions slot. Props: title, subtitle. --}}
@props(['title', 'subtitle' => null])

<div {{ $attributes->merge(['class' => 'flex flex-wrap items-start justify-between gap-4']) }}>
    <div class="min-w-0">
        <h1 class="break-words text-2xl font-semibold text-zinc-900 dark:text-zinc-100">{{ $title }}</h1>
        @if ($subtitle)
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $subtitle }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
