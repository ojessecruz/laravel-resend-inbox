<?php

declare(strict_types=1);

namespace Jessecruz\LaravelResendInbox\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Jessecruz\LaravelResendInbox\Database\Factories\InboxMessageFactory;
use Jessecruz\ResendInbox\DeliveryStatus;
use Jessecruz\ResendInbox\Direction;
use Jessecruz\ResendInbox\Headers;

/**
 * One email in a conversation. Bodies and headers are stored locally;
 * attachments keep only Resend metadata and are downloaded on demand.
 * message_id / in_reply_to are stored without angle brackets.
 *
 * @property int $id
 * @property int $inbox_thread_id
 * @property Direction $direction
 * @property string|null $resend_id
 * @property string|null $message_id
 * @property string|null $in_reply_to
 * @property string|null $references
 * @property string $mailbox
 * @property string $from_address
 * @property string|null $from_name
 * @property list<string> $to
 * @property list<string>|null $cc
 * @property list<string>|null $reply_to
 * @property string $subject
 * @property string|null $html
 * @property string|null $text
 * @property array<string, mixed>|null $headers
 * @property list<array{id: string, filename?: string|null, content_type?: string|null, size?: int|null}>|null $attachments
 * @property DeliveryStatus|null $delivery_status
 * @property string|null $bounce_message
 * @property int|null $sent_by
 * @property CarbonInterface $sent_at
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read InboxThread $thread
 * @property-read Model|null $sender
 */
final class InboxMessage extends Model
{
    /** @use HasFactory<InboxMessageFactory> */
    use HasFactory;

    protected $table = 'inbox_messages';

    protected $fillable = [
        'inbox_thread_id',
        'direction',
        'resend_id',
        'message_id',
        'in_reply_to',
        'references',
        'mailbox',
        'from_address',
        'from_name',
        'to',
        'cc',
        'reply_to',
        'subject',
        'html',
        'text',
        'headers',
        'attachments',
        'delivery_status',
        'bounce_message',
        'sent_by',
        'sent_at',
    ];

    /**
     * @return BelongsTo<InboxThread, $this>
     */
    public function thread(): BelongsTo
    {
        return $this->belongsTo(InboxThread::class, 'inbox_thread_id');
    }

    /**
     * Who sent the email from the inbox (config "inbox.user_model").
     *
     * @return BelongsTo<Model, $this>
     */
    public function sender(): BelongsTo
    {
        /** @var class-string<Model> $model */
        $model = config('inbox.user_model');

        return $this->belongsTo($model, 'sent_by');
    }

    public function isInbound(): bool
    {
        return $this->direction === Direction::Inbound;
    }

    public function fromLabel(): string
    {
        return $this->from_name !== null && $this->from_name !== ''
            ? "{$this->from_name} <{$this->from_address}>"
            : $this->from_address;
    }

    /**
     * Where a reply to this message goes: Reply-To when present, otherwise
     * the sender.
     */
    public function replyAddress(): string
    {
        return $this->reply_to[0] ?? $this->from_address;
    }

    public function header(string $name): ?string
    {
        return Headers::get($this->headers ?? [], $name);
    }

    /**
     * Whether the attachment id is one Resend reported for this email.
     */
    public function hasAttachment(string $attachmentId): bool
    {
        foreach ($this->attachments ?? [] as $attachment) {
            if ($attachment['id'] === $attachmentId) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'direction' => Direction::class,
            'delivery_status' => DeliveryStatus::class,
            'to' => 'array',
            'cc' => 'array',
            'reply_to' => 'array',
            'headers' => 'array',
            'attachments' => 'array',
            'sent_at' => 'datetime',
        ];
    }

    protected static function newFactory(): InboxMessageFactory
    {
        return InboxMessageFactory::new();
    }
}
