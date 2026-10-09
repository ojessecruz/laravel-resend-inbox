<?php

declare(strict_types=1);

namespace Jessecruz\LaravelResendInbox\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Jessecruz\LaravelResendInbox\Models\InboxMessage;
use Jessecruz\LaravelResendInbox\Models\InboxThread;
use Jessecruz\LaravelResendInbox\Support\InboxConfig;
use Jessecruz\ResendInbox\Composing\Conversation;
use Jessecruz\ResendInbox\Composing\ReplyBuilder;
use Jessecruz\ResendInbox\DeliveryStatus;
use Jessecruz\ResendInbox\Direction;
use Jessecruz\ResendInbox\Exceptions\DisallowedSender;
use Jessecruz\ResendInbox\Exceptions\MailboxException;
use Jessecruz\ResendInbox\ResendMailbox;

final readonly class SendEmail
{
    public function __construct(private ResendMailbox $resend) {}

    /**
     * Send a Markdown email written in the inbox and store it in its
     * conversation, with the sender's signature appended. Nothing is stored
     * when Resend rejects the send.
     *
     * @param  list<string>  $to
     *
     * @throws DisallowedSender
     * @throws MailboxException
     */
    public function handle(?Model $author, string $from, array $to, string $subject, string $markdown, ?InboxThread $thread = null): InboxMessage
    {
        $composed = (new ReplyBuilder(InboxConfig::mailbox()))->build(
            from: $from,
            to: $to,
            subject: $subject,
            markdown: self::withSignature($markdown, $from),
            conversation: $thread !== null ? self::conversation($thread) : null,
        );

        $resendId = $this->resend->send($composed->email);

        return DB::transaction(function () use ($author, $composed, $thread, $resendId): InboxMessage {
            $thread ??= InboxThread::query()->create([
                'subject' => $composed->email->subject,
                'mailbox' => $composed->fromAddress,
                'last_message_at' => now(),
            ]);

            $message = $thread->messages()->create([
                'direction' => Direction::Outbound,
                'resend_id' => $resendId,
                'message_id' => $composed->messageId,
                'in_reply_to' => $composed->inReplyTo,
                'references' => $composed->references !== [] ? implode(' ', $composed->references) : null,
                'mailbox' => $composed->fromAddress,
                'from_address' => $composed->fromAddress,
                'from_name' => InboxConfig::string('inbox.sender_name'),
                'to' => $composed->email->to,
                'subject' => $composed->email->subject,
                'html' => $composed->email->html,
                'text' => $composed->email->text,
                'delivery_status' => DeliveryStatus::Sent,
                'sent_by' => $author?->getKey(),
                'sent_at' => now(),
            ]);

            $thread->update(['last_message_at' => now(), 'read_at' => now()]);

            return $message;
        });
    }

    private static function withSignature(string $markdown, string $from): string
    {
        $signature = InboxConfig::signatureFor($from);

        return $signature === null ? $markdown : rtrim($markdown)."\n\n".trim($signature);
    }

    private static function conversation(InboxThread $thread): Conversation
    {
        $messages = $thread->messages()
            ->oldest('sent_at')
            ->oldest('id')
            ->get(['message_id', 'direction', 'mailbox']);

        $messageIds = $messages->pluck('message_id')->filter()->values()->all();

        return new Conversation(
            messageIds: array_values(array_map(strval(...), $messageIds)),
            latestMessageId: $messageIds === [] ? null : (string) end($messageIds),
            mailboxes: array_values($messages
                ->filter(fn (InboxMessage $message): bool => $message->isInbound())
                ->pluck('mailbox')
                ->map(strval(...))
                ->unique()
                ->all()),
        );
    }
}
