<?php

declare(strict_types=1);

use Jessecruz\LaravelResendInbox\Tests\Stubs\User;
use Jessecruz\LaravelResendInbox\Tests\Support\FakeResend;
use Jessecruz\LaravelResendInbox\Tests\TestCase;
use Jessecruz\ResendInbox\ReceivedEmail;
use Jessecruz\ResendInbox\ResendMailbox;
use Resend\Client;

uses(TestCase::class)->in(__DIR__);

/**
 * Swap the Resend API for an in-memory fake.
 */
function fakeResend(): FakeResend
{
    $fake = new FakeResend;

    app()->instance(ResendMailbox::class, new ResendMailbox(new Client($fake)));

    return $fake;
}

function admin(): User
{
    return User::query()->create(['name' => 'Jessé', 'email' => 'jesse@example.com', 'is_admin' => true]);
}

/**
 * Inbound email as the receiving API returns it, with overrides.
 *
 * @param  array<string, mixed>  $overrides
 */
function receivedEmail(array $overrides = []): ReceivedEmail
{
    return ReceivedEmail::fromApi(array_merge([
        'id' => 'rcv_1',
        'from' => 'Maria Silva <Maria@Acme.com>',
        'to' => ['contato@elenya.app'],
        'cc' => [],
        'reply_to' => [],
        'received_for' => ['contato@elenya.app'],
        'subject' => 'Preço para 50 profissionais',
        'html' => '<p>Olá</p>',
        'text' => 'Olá',
        'message_id' => '<abc-123@mail.acme.com>',
        'headers' => [],
        'attachments' => [['id' => 'att_1', 'filename' => 'proposta.pdf', 'content_type' => 'application/pdf', 'size' => 2048]],
        'created_at' => '2026-10-09T12:00:00Z',
    ], $overrides));
}

/**
 * Svix headers Resend would send for the body.
 *
 * @return array<string, string>
 */
function resendWebhookHeaders(string $body, ?int $timestamp = null): array
{
    $id = 'msg_'.bin2hex(random_bytes(4));
    $timestamp ??= time();
    $signature = base64_encode(hash_hmac('sha256', "{$id}.{$timestamp}.{$body}", 'resend-test-secret', true));

    return [
        'svix-id' => $id,
        'svix-timestamp' => (string) $timestamp,
        'svix-signature' => "v1,{$signature}",
    ];
}

/**
 * @param  array<string, mixed>  $payload
 * @param  array<string, string>|null  $headers
 */
function postResendWebhook(array $payload, ?array $headers = null): mixed
{
    $body = json_encode($payload, JSON_THROW_ON_ERROR);
    $server = ['CONTENT_TYPE' => 'application/json'];

    foreach ($headers ?? resendWebhookHeaders($body) as $key => $value) {
        $server['HTTP_'.strtoupper(str_replace('-', '_', $key))] = $value;
    }

    return test()->call('POST', route('inbox.webhook'), [], [], [], $server, $body);
}
