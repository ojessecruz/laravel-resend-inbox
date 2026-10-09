# Changelog

All notable changes to `laravel-resend-inbox` will be documented in this file.

## 0.1.1 - 2026-10-09

- New `card` component for the panel around the conversation list and the reply form, which were fixed inside the Livewire views: an app can now restyle them without overriding a whole screen.
- The email body takes the text and background colors of the `email-body` iframe instead of a fixed white and near-black, so classes on the component restyle it.
- Requires `ojessecruz/resend-inbox` 0.1.1, which stores received emails at the right time in apps outside UTC.

## 0.1.0 - 2026-10-09

- First version, extracted from the Omni Line backoffice inbox on top of [`ojessecruz/resend-inbox`](https://github.com/ojessecruz/resend-inbox): signed webhook route, queued processing, conversations and messages, one tab per configured address plus "Others", replies from the address that received the conversation, per-address signatures, delivery status, attachments downloaded from Resend, `viewInbox` gate checked on every Livewire request, overridable Blade components, English and Portuguese translations.
- Requires Laravel 12 or 13: every Laravel 11 release now carries unpatched security advisories, and Composer refuses to install it.
