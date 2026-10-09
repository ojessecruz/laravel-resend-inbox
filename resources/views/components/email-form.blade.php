{{-- Compose / reply fields. Props: form (the EmailForm property name), senders (list of addresses). --}}
@props(['form', 'senders'])

<div {{ $attributes->merge(['class' => 'space-y-4']) }}>
    <div class="grid items-start gap-4 sm:grid-cols-2">
        <x-inbox::field :label="__('inbox::inbox.fields.from')" :name="$form.'.from'">
            <x-inbox::select id="{{ $form }}.from" wire:model="{{ $form }}.from" :options="array_combine($senders, $senders)" />
        </x-inbox::field>

        <x-inbox::field :label="__('inbox::inbox.fields.to')" :name="$form.'.to'" :hint="__('inbox::inbox.fields.to_hint')">
            <x-inbox::input id="{{ $form }}.to" wire:model="{{ $form }}.to" />
        </x-inbox::field>
    </div>

    <x-inbox::field :label="__('inbox::inbox.fields.subject')" :name="$form.'.subject'">
        <x-inbox::input id="{{ $form }}.subject" wire:model="{{ $form }}.subject" />
    </x-inbox::field>

    <x-inbox::field :label="__('inbox::inbox.fields.body')" :name="$form.'.body'" :hint="__('inbox::inbox.fields.body_hint')">
        <x-inbox::textarea id="{{ $form }}.body" wire:model="{{ $form }}.body" />
    </x-inbox::field>
</div>
