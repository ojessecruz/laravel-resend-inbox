{{-- Button or link. Props: variant (primary|secondary), size (md|sm), href. --}}
@props(['variant' => 'secondary', 'size' => 'md', 'href' => null])

@php
    $classes = implode(' ', [
        'inline-flex items-center justify-center gap-2 rounded-lg font-medium transition focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:opacity-50',
        $size === 'sm' ? 'px-2.5 py-1 text-xs' : 'px-4 py-2 text-sm',
        $variant === 'primary'
            ? 'bg-zinc-900 text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200'
            : 'border border-zinc-300 bg-white text-zinc-800 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800',
    ]);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->merge(['type' => 'button', 'class' => $classes]) }}>{{ $slot }}</button>
@endif
