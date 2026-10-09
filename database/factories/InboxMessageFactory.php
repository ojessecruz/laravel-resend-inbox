<?php

declare(strict_types=1);

namespace Jessecruz\LaravelResendInbox\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Jessecruz\LaravelResendInbox\Models\InboxMessage;
use Jessecruz\LaravelResendInbox\Models\InboxThread;
use Jessecruz\ResendInbox\DeliveryStatus;
use Jessecruz\ResendInbox\Direction;

/**
 * @extends Factory<InboxMessage>
 */
final class InboxMessageFactory extends Factory
{
    protected $model = InboxMessage::class;

    /**
     * An inbound email to hello@example.com.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subject = $this->faker->sentence(4);

        return [
            'inbox_thread_id' => InboxThread::factory()->state(['subject' => $subject]),
            'direction' => Direction::Inbound,
            'resend_id' => 'rcv_'.$this->faker->unique()->uuid(),
            'message_id' => $this->faker->unique()->uuid().'@mail.example.org',
            'mailbox' => 'hello@example.com',
            'from_address' => $this->faker->unique()->safeEmail(),
            'from_name' => $this->faker->name(),
            'to' => ['hello@example.com'],
            'subject' => $subject,
            'html' => '<p>'.$this->faker->sentence().'</p>',
            'text' => $this->faker->sentence(),
            'sent_at' => now(),
        ];
    }

    /**
     * An email sent from the inbox to a customer.
     */
    public function outbound(): self
    {
        return $this->state(fn (): array => [
            'direction' => Direction::Outbound,
            'resend_id' => 'snd_'.$this->faker->unique()->uuid(),
            'message_id' => $this->faker->unique()->uuid().'@example.com',
            'from_address' => 'hello@example.com',
            'from_name' => 'Example',
            'to' => [$this->faker->unique()->safeEmail()],
            'delivery_status' => DeliveryStatus::Sent,
        ]);
    }
}
