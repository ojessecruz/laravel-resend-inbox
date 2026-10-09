<?php

declare(strict_types=1);

namespace Jessecruz\LaravelResendInbox\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Jessecruz\LaravelResendInbox\Models\InboxThread;

/**
 * @extends Factory<InboxThread>
 */
final class InboxThreadFactory extends Factory
{
    protected $model = InboxThread::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subject' => $this->faker->sentence(4),
            'mailbox' => 'hello@example.com',
            'last_message_at' => now(),
        ];
    }

    public function read(): self
    {
        return $this->state(['read_at' => now()]);
    }

    public function archived(): self
    {
        return $this->state(['archived_at' => now()]);
    }
}
