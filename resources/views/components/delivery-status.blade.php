{{-- Props: message (InboxMessage). --}}
@props(['message'])

@if ($message->delivery_status)
    <x-inbox::badge
        :tone="match ($message->delivery_status) {
            \Jessecruz\ResendInbox\DeliveryStatus::Delivered => 'success',
            \Jessecruz\ResendInbox\DeliveryStatus::Bounced, \Jessecruz\ResendInbox\DeliveryStatus::Complained => 'danger',
            default => 'neutral',
        }"
        :title="$message->bounce_message"
    >{{ __('inbox::inbox.delivery.'.$message->delivery_status->value) }}</x-inbox::badge>
@endif
