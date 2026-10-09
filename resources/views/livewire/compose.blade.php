<div class="max-w-3xl space-y-6">
    <div class="space-y-2">
        <a href="{{ route('inbox.index') }}" class="text-sm text-zinc-600 hover:underline dark:text-zinc-400">&larr; {{ __('inbox::inbox.titles.inbox') }}</a>
        <x-inbox::heading :title="__('inbox::inbox.titles.compose')" />
    </div>

    <form wire:submit="send" class="space-y-4">
        <x-inbox::email-form form="form" :senders="$this->senders" />

        <div class="flex justify-end">
            <x-inbox::button type="submit" variant="primary" wire:loading.attr="disabled">{{ __('inbox::inbox.actions.send') }}</x-inbox::button>
        </div>
    </form>
</div>
