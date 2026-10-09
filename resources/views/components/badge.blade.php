{{-- Props: tone (neutral|success|danger|info). --}}
@props(['tone' => 'neutral'])

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium '.match ($tone) {
    'success' => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
    'danger' => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300',
    'info' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
    default => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300',
}]) }}>{{ $slot }}</span>
