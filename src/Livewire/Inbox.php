<?php

declare(strict_types=1);

namespace Jessecruz\LaravelResendInbox\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Jessecruz\LaravelResendInbox\Livewire\Concerns\AuthorizesInbox;
use Jessecruz\LaravelResendInbox\Models\InboxThread;
use Jessecruz\LaravelResendInbox\Support\InboxConfig;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * The conversation list: one tab per configured address plus "Others" for
 * any other address on the domain, a status filter, search and bulk
 * archiving.
 *
 * @property-read LengthAwarePaginator<int, InboxThread> $threads
 * @property-read array<string, int> $unreadByTab
 */
final class Inbox extends Component
{
    use AuthorizesInbox;
    use WithPagination;

    public const string OTHERS = 'others';

    /** A configured address, "others", or empty for every conversation. */
    #[Url]
    public string $tab = '';

    /** inbox (not archived), unread, or archived. */
    #[Url]
    public string $status = 'inbox';

    #[Url]
    public string $search = '';

    /**
     * Ids of the ticked conversations, as strings from the checkboxes.
     *
     * @var list<string>
     */
    public array $selected = [];

    /** Header checkbox: every conversation on the current page. */
    public bool $selectPage = false;

    public ?string $notice = null;

    public function updated(string $property): void
    {
        if (in_array($property, ['tab', 'status', 'search'], true)) {
            $this->resetPage();
            $this->clearSelection();
        }
    }

    public function updatedSelectPage(bool $checked): void
    {
        $this->selected = $checked ? $this->pageIds() : [];
    }

    public function updatedSelected(): void
    {
        $pageIds = $this->pageIds();

        $this->selectPage = $pageIds !== [] && array_diff($pageIds, $this->selected) === [];
    }

    public function updatedPaginators(): void
    {
        $this->clearSelection();
    }

    public function archiveSelected(): void
    {
        $count = $this->applyToSelected(['archived_at' => now()]);

        $this->notice = $count > 0 ? trans_choice('inbox::inbox.notices.archived', $count, ['count' => $count]) : null;
    }

    public function unarchiveSelected(): void
    {
        $count = $this->applyToSelected(['archived_at' => null]);

        $this->notice = $count > 0 ? trans_choice('inbox::inbox.notices.unarchived', $count, ['count' => $count]) : null;
    }

    public function markSelectedRead(): void
    {
        $count = $this->applyToSelected(['read_at' => now()]);

        $this->notice = $count > 0 ? trans_choice('inbox::inbox.notices.marked_read', $count, ['count' => $count]) : null;
    }

    public function clearSelection(): void
    {
        $this->selected = [];
        $this->selectPage = false;
    }

    /**
     * Tab key => label: every configured address, then "Others".
     *
     * @return array<string, string>
     */
    #[Computed]
    public function tabs(): array
    {
        $tabs = ['' => __('inbox::inbox.tabs.all')];

        foreach (InboxConfig::mailboxes() as $address) {
            $tabs[$address] = $address;
        }

        $tabs[self::OTHERS] = __('inbox::inbox.tabs.others');

        return $tabs;
    }

    /**
     * Unread conversations per tab, for the counters.
     *
     * @return array<string, int>
     */
    #[Computed]
    public function unreadByTab(): array
    {
        $byMailbox = InboxThread::query()
            ->unread()
            ->selectRaw('mailbox, count(*) as aggregate')
            ->groupBy('mailbox')
            ->pluck('aggregate', 'mailbox')
            ->map(fn (mixed $count): int => is_numeric($count) ? (int) $count : 0);

        $configured = InboxConfig::mailboxes();
        $counts = ['' => (int) $byMailbox->sum()];

        foreach ($configured as $address) {
            $counts[$address] = $byMailbox->get($address, 0);
        }

        $counts[self::OTHERS] = (int) $byMailbox->except($configured)->sum();

        return $counts;
    }

    /**
     * One row per conversation, most recent activity first.
     *
     * @return LengthAwarePaginator<int, InboxThread>
     */
    #[Computed]
    public function threads(): LengthAwarePaginator
    {
        $search = trim($this->search);
        $configured = InboxConfig::mailboxes();
        $perPage = config('inbox.per_page', 25);

        return InboxThread::query()
            ->with('latestMessage')
            ->withCount('messages')
            ->when(
                $this->status === 'archived',
                fn (Builder $query) => $query->whereNotNull('archived_at'),
                fn (Builder $query) => $query->whereNull('archived_at'),
            )
            ->when($this->status === 'unread', fn (Builder $query) => $query->whereNull('read_at'))
            ->when($this->tab === self::OTHERS, fn (Builder $query) => $query->whereNotIn('mailbox', $configured))
            ->when(
                $this->tab !== '' && $this->tab !== self::OTHERS,
                fn (Builder $query) => $query->where('mailbox', $this->tab),
            )
            ->when($search !== '', fn (Builder $query) => $query->where(
                fn (Builder $query) => $query
                    ->where('subject', 'like', "%{$search}%")
                    ->orWhereHas('messages', fn (Builder $query) => $query
                        ->where('from_address', 'like', "%{$search}%")
                        ->orWhere('from_name', 'like', "%{$search}%")),
            ))
            ->latest('last_message_at')
            ->latest('id')
            ->paginate(is_numeric($perPage) ? (int) $perPage : 25);
    }

    public function render(): View
    {
        return view('inbox::livewire.inbox')
            ->layout(InboxConfig::string('inbox.layout'), ['title' => __('inbox::inbox.titles.inbox')]);
    }

    /**
     * Conversation ids on the current page.
     *
     * @return list<string>
     */
    private function pageIds(): array
    {
        return array_values(array_map(
            fn (InboxThread $thread): string => (string) $thread->id,
            $this->threads->items(),
        ));
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return int Conversations updated.
     */
    private function applyToSelected(array $attributes): int
    {
        $ids = array_map(intval(...), $this->selected);

        if ($ids === []) {
            return 0;
        }

        $count = InboxThread::query()->whereKey($ids)->update($attributes);

        $this->clearSelection();
        unset($this->threads, $this->unreadByTab);

        return $count;
    }
}
