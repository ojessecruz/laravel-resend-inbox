{{-- Label, control slot, hint and the validation error of "name". --}}
@props(['label', 'name', 'hint' => null])

<div {{ $attributes->merge(['class' => 'space-y-1']) }}>
    <label for="{{ $name }}" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ $label }}</label>
    {{ $slot }}
    @if ($hint)
        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $hint }}</p>
    @endif
    @error($name)
        <p class="text-xs font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror
</div>
