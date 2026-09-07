<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Message;
use App\Models\Sender;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'sender_id' => Sender::factory(),
            'gmail_id' => Str::lower(Str::random(16)),
            'thread_id' => Str::lower(Str::random(16)),
            'subject' => fake()->sentence(4),
            'snippet' => fake()->sentence(12),
            'received_at' => now()->subDays(fake()->numberBetween(0, 20)),
            'is_read' => false,
            'is_in_inbox' => true,
            'label_ids' => ['INBOX', 'UNREAD', 'CATEGORY_PROMOTIONS'],
            'list_unsubscribe' => '<https://example.com/unsub>',
            'category' => 'promo',
        ];
    }
}
