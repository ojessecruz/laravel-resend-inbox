# Changelog

All notable changes to `laravel-resend-inbox` will be documented in this file.

## 0.1.0 - 2026-10-09

- First version, extracted from the Omni Line backoffice inbox on top of [`ojessecruz/resend-inbox`](https://github.com/ojessecruz/resend-inbox): signed webhook route, queued processing, conversations and messages, one tab per configured address plus "Others", replies from the address that received the conversation, per-address signatures, delivery status, attachments downloaded from Resend, `viewInbox` gate checked on every Livewire request, overridable Blade components, English and Portuguese translations.
- Requires Laravel 12 or 13: every Laravel 11 release now carries unpatched security advisories, and Composer refuses to install it.
