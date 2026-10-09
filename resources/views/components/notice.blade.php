{{-- Success message after an action. Props: message. --}}
@props(['message' => null])

@if ($message)
    <div {{ $attributes->merge(['class' => 'rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-900 dark:bg-green-950/40 dark:text-green-300']) }} role="status">
        {{ $message }}
    </div>
@endif
