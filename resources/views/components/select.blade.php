{{-- Props: options (value => label). --}}
@props(['options' => []])

<select {{ $attributes->merge([
    'class' => 'block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100',
]) }}>
    @foreach ($options as $value => $label)
        <option value="{{ $value }}">{{ $label }}</option>
    @endforeach
</select>
