<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Domain
    |--------------------------------------------------------------------------
    |
    | The domain whose inbound mail belongs in the inbox. A Resend account
    | receives mail for every domain it hosts; mail addressed only to other
    | domains is dropped.
    |
    */

    'domain' => env('INBOX_DOMAIN'),

    /*
    |--------------------------------------------------------------------------
    | Mailboxes
    |--------------------------------------------------------------------------
    |
    | Addresses on the domain shown as tabs and allowed to send a new email;
    | the first one is the default sender. Mail to any other address on the
    | domain is kept under the "Others" tab, never dropped.
    |
    */

    'mailboxes' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('INBOX_MAILBOXES', '')),
    ))),

    /*
    |--------------------------------------------------------------------------
    | Sender name and signatures
    |--------------------------------------------------------------------------
    |
    | The display name on every email sent from the inbox, and an optional
    | Markdown signature per address, appended below the message.
    |
    */

    'sender_name' => env('INBOX_SENDER_NAME', env('APP_NAME', 'Laravel')),

    'signatures' => [
        // 'support@example.com' => "Jane\nExample Support",
    ],

    /*
    |--------------------------------------------------------------------------
    | Resend
    |--------------------------------------------------------------------------
    |
    | The API key (the same one Laravel's Resend mail transport uses) and the
    | signing secret of the webhook posting to the webhook route below.
    |
    */

    'resend' => [
        'key' => env('RESEND_KEY'),
        'webhook_secret' => env('RESEND_WEBHOOK_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Routes
    |--------------------------------------------------------------------------
    |
    | The inbox screens live under the prefix with the given middleware; the
    | "viewInbox" gate is checked on top of it on every Livewire request.
    | The webhook route is public and verified by its signature.
    |
    */

    'routes' => [
        'prefix' => 'inbox',
        'middleware' => ['web', 'auth'],
        'webhook_path' => 'resend/inbox-webhook',
    ],

    /*
    |--------------------------------------------------------------------------
    | Layout
    |--------------------------------------------------------------------------
    |
    | The Blade layout wrapping the inbox screens; it must render {{ $slot }}.
    | Point it at your admin layout so the inbox inherits your navigation and
    | your compiled Tailwind CSS.
    |
    */

    'layout' => 'inbox::layouts.app',

    /*
    |--------------------------------------------------------------------------
    | Queue
    |--------------------------------------------------------------------------
    |
    | Where webhook events are processed. Null uses the default connection
    | and queue.
    |
    */

    'queue' => [
        'connection' => env('INBOX_QUEUE_CONNECTION'),
        'name' => env('INBOX_QUEUE'),
    ],

    /*
    |--------------------------------------------------------------------------
    | User model
    |--------------------------------------------------------------------------
    |
    | Who sent an email from the inbox. Shown next to outbound messages.
    |
    */

    'user_model' => 'App\\Models\\User',

    'per_page' => 25,

];
