# Laravel Resend Inbox

A shared inbox inside your Laravel admin, on top of [Resend](https://resend.com) inbound email.

- Mail to your domain arrives through a signed webhook and is grouped into conversations.
- One tab per address you configure (`hello@`, `support@`, `billing@`...) plus **Others** for any other address on the domain: nothing is dropped.
- Reply from the address that received the conversation, in Markdown, with a signature per address. Replies thread correctly in the customer's mail client.
- Delivery status (`sent`, `delivered`, `bounced`, `marked as spam`) from Resend webhooks.
- Auto-replies and bounces are stored but raise no events.
- Remote images in emails are blocked until you load them; email HTML renders in a sandboxed iframe.
- Livewire screens built from small Blade components you can override one file at a time.

The framework-agnostic logic (threading, reply headers, webhook verification, auto-reply detection) lives in [`ojessecruz/resend-inbox`](https://github.com/ojessecruz/resend-inbox).

## Requirements

PHP 8.3+, Laravel 11–13, Livewire 3 or 4, Tailwind CSS in the host app, and a Resend domain with receiving enabled.

## Installation

```bash
composer require ojessecruz/laravel-resend-inbox
php artisan vendor:publish --tag="resend-inbox-config"
php artisan vendor:publish --tag="resend-inbox-migrations"
php artisan migrate
```

```env
INBOX_DOMAIN=example.com
INBOX_MAILBOXES=hello@example.com,support@example.com,billing@example.com
INBOX_SENDER_NAME="Jane from Example"
RESEND_KEY=re_...
RESEND_WEBHOOK_SECRET=whsec_...
```

In the Resend dashboard, add a webhook pointing to `https://your-app.com/resend/inbox-webhook` with the `email.received`, `email.sent`, `email.delivered`, `email.bounced` and `email.complained` events, and copy its signing secret.

### Who can open the inbox

The screens use the middleware in `inbox.routes.middleware` (`web`, `auth` by default) **and** the `viewInbox` gate, checked on every Livewire request. Livewire actions go through Livewire's own update route, which skips your page middleware, so the gate is what protects them. The gate is closed outside the `local` environment until you define it:

```php
// app/Providers/AppServiceProvider.php
use Illuminate\Support\Facades\Gate;

public function boot(): void
{
    Gate::define('viewInbox', fn (User $user): bool => $user->isAdmin());
}
```

### Layout and styles

Point `inbox.layout` at your admin layout; it must render `{{ $slot }}`.

The views use Tailwind utility classes compiled by your app. On Tailwind v4, import the package source file from your CSS entrypoint:

```css
@import '../../vendor/ojessecruz/laravel-resend-inbox/resources/css/inbox.css';
```

On Tailwind v3, add the views to `content` in `tailwind.config.js`:

```js
content: [
    './vendor/ojessecruz/laravel-resend-inbox/resources/views/**/*.blade.php',
],
```

### Signatures

```php
// config/inbox.php
'signatures' => [
    'support@example.com' => "Jane\nExample Support",
],
```

The signature is appended below every email sent from that address.

## Getting notified

Listen to the package events:

| Event | When |
|---|---|
| `Jessecruz\LaravelResendInbox\Events\InboxEmailReceived` | A person wrote to one of your addresses (not an auto-reply or a bounce). Once per email. |
| `Jessecruz\LaravelResendInbox\Events\InboxEmailDeliveryFailed` | An email sent from the inbox bounced or was marked as spam. Once. |

```php
Event::listen(function (InboxEmailReceived $event) {
    $message = $event->message;

    // Send yourself a Slack/Telegram alert with $message->fromLabel(),
    // $message->subject and route('inbox.threads.show', $message->inbox_thread_id).
});
```

For an unread badge in your menu: `InboxThread::query()->unread()->count()`.

## Customizing the screens

Each piece of the screens is an anonymous Blade component under the `inbox::` namespace. Override one by creating a file with the same name in `resources/views/vendor/inbox/components/`; every other piece keeps coming from the package and keeps receiving its updates.

| Component | What it is |
|---|---|
| `button`, `input`, `textarea`, `select`, `checkbox`, `badge`, `field`, `notice`, `heading` | Form and UI primitives. Override these to match your design system. |
| `tabs` | The mailbox tabs with unread counters. |
| `thread-row` | One conversation in the list. |
| `message` | One email in a conversation. |
| `email-body` | The sandboxed iframe rendering an email's HTML. |
| `email-form` | From / To / Subject / Message fields. |
| `delivery-status` | The delivery status badge. |

To copy every view at once: `php artisan vendor:publish --tag="resend-inbox-views"`. Published views stop receiving package updates.

The props of these components and the public properties and methods of the Livewire components (`Inbox`, `ShowThread`, `Compose`) are the package's public API: breaking changes to them only happen in major versions.

## Translations

English and Brazilian Portuguese are included. Publish them with `php artisan vendor:publish --tag="resend-inbox-translations"`.

## Testing

```bash
composer test
composer analyse
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
