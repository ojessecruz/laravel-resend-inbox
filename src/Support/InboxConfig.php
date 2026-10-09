<?php

declare(strict_types=1);

namespace Jessecruz\LaravelResendInbox\Support;

use Jessecruz\ResendInbox\EmailAddress;
use Jessecruz\ResendInbox\Mailbox;

/**
 * Typed access to config/inbox.php.
 */
final class InboxConfig
{
    public static function mailbox(): Mailbox
    {
        return new Mailbox(self::domain(), self::mailboxes(), self::string('inbox.sender_name'));
    }

    public static function domain(): string
    {
        $domain = self::string('inbox.domain');

        if ($domain !== '') {
            return mb_strtolower($domain);
        }

        $first = self::mailboxes()[0] ?? '';

        return $first === '' ? '' : EmailAddress::domain($first);
    }

    /**
     * Configured addresses, lowercased, in tab order.
     *
     * @return list<string>
     */
    public static function mailboxes(): array
    {
        $mailboxes = config('inbox.mailboxes', []);

        return array_values(array_unique(array_map(
            static fn (mixed $address): string => EmailAddress::address(is_scalar($address) ? (string) $address : ''),
            is_array($mailboxes) ? $mailboxes : [],
        )));
    }

    /**
     * Markdown signature for an address, or null.
     */
    public static function signatureFor(string $address): ?string
    {
        $signatures = config('inbox.signatures', []);

        foreach (is_array($signatures) ? $signatures : [] as $key => $signature) {
            if (EmailAddress::address((string) $key) === EmailAddress::address($address) && is_string($signature) && trim($signature) !== '') {
                return $signature;
            }
        }

        return null;
    }

    public static function string(string $key): string
    {
        $value = config($key);

        return is_scalar($value) ? (string) $value : '';
    }
}
