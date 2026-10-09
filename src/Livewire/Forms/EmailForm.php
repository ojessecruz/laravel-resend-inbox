<?php

declare(strict_types=1);

namespace Jessecruz\LaravelResendInbox\Livewire\Forms;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Jessecruz\LaravelResendInbox\Actions\SendEmail;
use Jessecruz\LaravelResendInbox\Models\InboxMessage;
use Jessecruz\LaravelResendInbox\Models\InboxThread;
use Jessecruz\ResendInbox\EmailAddress;
use Jessecruz\ResendInbox\Exceptions\DisallowedSender;
use Jessecruz\ResendInbox\Exceptions\MailboxException;
use Livewire\Form;

/**
 * Fields shared by the compose page and the reply box. The allowed From
 * addresses are enforced by the SendEmail action.
 */
final class EmailForm extends Form
{
    public string $from = '';

    /** Comma-separated recipient addresses. */
    public string $to = '';

    public string $subject = '';

    /** Markdown body. */
    public string $body = '';

    /**
     * Validate and send; returns null (with the error on the form) when the
     * sender is not allowed or Resend rejects the email.
     */
    public function send(SendEmail $action, ?InboxThread $thread = null): ?InboxMessage
    {
        $this->validate();

        $author = Auth::user();

        try {
            return $action->handle(
                $author instanceof Model ? $author : null,
                $this->from,
                EmailAddress::list($this->to),
                $this->subject,
                $this->body,
                $thread,
            );
        } catch (DisallowedSender) {
            $this->addError('from', __('inbox::inbox.validation.sender'));
        } catch (MailboxException $e) {
            report($e);

            $this->addError('body', __('inbox::inbox.validation.send_failed'));
        }

        return null;
    }

    /**
     * @return array<string, list<mixed>>
     */
    protected function rules(): array
    {
        return [
            'from' => ['required', 'email'],
            'to' => ['required', 'string', function (string $attribute, mixed $value, Closure $fail): void {
                $recipients = EmailAddress::list(is_string($value) ? $value : '');

                if ($recipients === []) {
                    $fail(__('inbox::inbox.validation.recipients'));

                    return;
                }

                foreach ($recipients as $recipient) {
                    if (filter_var($recipient, FILTER_VALIDATE_EMAIL) === false) {
                        $fail(__('inbox::inbox.validation.recipient', ['address' => $recipient]));

                        return;
                    }
                }
            }],
            'subject' => ['required', 'string', 'max:998'],
            'body' => ['required', 'string', 'max:100000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'from' => __('inbox::inbox.fields.from'),
            'to' => __('inbox::inbox.fields.to'),
            'subject' => __('inbox::inbox.fields.subject'),
            'body' => __('inbox::inbox.fields.body'),
        ];
    }
}
