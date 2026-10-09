<?php

declare(strict_types=1);

namespace Jessecruz\LaravelResendInbox\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Jessecruz\LaravelResendInbox\Database\Factories\InboxThreadFactory;

/**
 * A conversation: emails received for one of our addresses plus the replies
 * sent from the inbox. Read and archived state is shared by everyone who
 * can open the inbox. "mailbox" is our address the conversation is filed
 * under (the latest one that received mail, or the sender of a new email).
 *
 * @property int $id
 * @property string $subject
 * @property string $mailbox
 * @property CarbonInterface $last_message_at
 * @property CarbonInterface|null $read_at
 * @property CarbonInterface|null $archived_at
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
final class InboxThread extends Model
{
    /** @use HasFactory<InboxThreadFactory> */
    use HasFactory;

    protected $table = 'inbox_threads';

    protected $fillable = [
        'subject',
        'mailbox',
        'last_message_at',
        'read_at',
        'archived_at',
    ];

    /**
     * @return HasMany<InboxMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(InboxMessage::class);
    }

    /**
     * @return HasOne<InboxMessage, $this>
     */
    public function latestMessage(): HasOne
    {
        return $this->hasOne(InboxMessage::class)->latestOfMany('sent_at');
    }

    /**
     * Unread conversations still in the inbox (the menu badge).
     *
     * @param  Builder<InboxThread>  $query
     */
    public function scopeUnread(Builder $query): void
    {
        $query->whereNull('read_at')->whereNull('archived_at');
    }

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
            'read_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    protected static function newFactory(): InboxThreadFactory
    {
        return InboxThreadFactory::new();
    }
}
