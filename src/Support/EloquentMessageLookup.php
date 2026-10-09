<?php

declare(strict_types=1);

namespace Jessecruz\LaravelResendInbox\Support;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Builder;
use Jessecruz\LaravelResendInbox\Models\InboxMessage;
use Jessecruz\LaravelResendInbox\Models\InboxThread;
use Jessecruz\ResendInbox\Direction;
use Jessecruz\ResendInbox\Threading\MessageLookup;
use Jessecruz\ResendInbox\Threading\ThreadCandidate;

/**
 * The thread resolver's queries over inbox_threads / inbox_messages.
 */
final class EloquentMessageLookup implements MessageLookup
{
    public function threadOfLatestMessage(array $messageIds): int|string|null
    {
        $threadId = InboxMessage::query()
            ->whereIn('message_id', $messageIds)
            ->latest('sent_at')
            ->value('inbox_thread_id');

        return is_int($threadId) || is_string($threadId) ? $threadId : null;
    }

    public function recentThreadsWith(string $correspondent, DateTimeImmutable $since): iterable
    {
        return InboxThread::query()
            ->where('last_message_at', '>=', $since)
            ->whereHas('messages', fn (Builder $query) => $query->where(
                fn (Builder $query) => $query
                    ->where(fn (Builder $query) => $query
                        ->where('direction', Direction::Inbound)
                        ->where('from_address', $correspondent))
                    ->orWhere(fn (Builder $query) => $query
                        ->where('direction', Direction::Outbound)
                        ->whereJsonContains('to', $correspondent)),
            ))
            ->latest('last_message_at')
            ->lazy()
            ->map(fn (InboxThread $thread): ThreadCandidate => new ThreadCandidate($thread->id, $thread->subject));
    }
}
