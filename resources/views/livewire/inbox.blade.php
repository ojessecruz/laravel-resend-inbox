<div class="space-y-6">
    <x-inbox::heading :title="__('inbox::inbox.titles.inbox')">
        <x-slot:actions>
            <x-inbox::button variant="primary" :href="route('inbox.compose')" wire:navigate>{{ __('inbox::inbox.actions.compose') }}</x-inbox::button>
        </x-slot:actions>
    </x-inbox::heading>

    <x-inbox::notice :message="$notice" />

    <x-inbox::tabs :tabs="$this->tabs" :current="$tab" :counts="$this->unreadByTab" />

    <div class="flex flex-col gap-3 sm:flex-row">
        <x-inbox::input type="search" wire:model.live.debounce.300ms="search" :placeholder="__('inbox::inbox.list.search')" :aria-label="__('inbox::inbox.list.search')" class="sm:max-w-sm" />
        <x-inbox::select wire:model.live="status" :aria-label="__('inbox::inbox.list.status')" class="sm:max-w-48" :options="[
            'inbox' => __('inbox::inbox.status.inbox'),
            'unread' => __('inbox::inbox.status.unread'),
            'archived' => __('inbox::inbox.status.archived'),
        ]" />
    </div>

    <x-inbox::card>
        <x-slot:header>
            <x-inbox::checkbox wire:model.live="selectPage" :aria-label="__('inbox::inbox.actions.select_page')" />

            @if ($selected !== [])
                <span class="text-sm text-zinc-600 dark:text-zinc-400">{{ trans_choice('inbox::inbox.list.selected', count($selected), ['count' => count($selected)]) }}</span>
                @if ($status === 'archived')
                    <x-inbox::button size="sm" wire:click="unarchiveSelected">{{ __('inbox::inbox.actions.unarchive') }}</x-inbox::button>
                @else
                    <x-inbox::button size="sm" wire:click="archiveSelected">{{ __('inbox::inbox.actions.archive') }}</x-inbox::button>
                @endif
                <x-inbox::button size="sm" wire:click="clearSelection">{{ __('inbox::inbox.actions.clear_selection') }}</x-inbox::button>
            @endif
        </x-slot:header>

        @if ($this->threads->isEmpty())
            <p class="px-4 py-10 text-center text-sm text-zinc-500">{{ __('inbox::inbox.list.empty') }}</p>
        @else
            <ul class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @foreach ($this->threads as $thread)
                    <x-inbox::thread-row :thread="$thread" wire:key="thread-{{ $thread->id }}" />
                @endforeach
            </ul>
        @endif
    </x-inbox::card>

    {{ $this->threads->links() }}
</div>
