<?php

namespace Database\Factories;

use App\Enums\SenderCategory;
use App\Enums\SenderRecommendation;
use App\Enums\SenderStatus;
use App\Models\Account;
use App\Models\Sender;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sender>
 */
class SenderFactory extends Factory
{
    public function definition(): array
    {
        $email = fake()->unique()->companyEmail();

        return [
            'account_id' => Account::factory(),
            'email' => strtolower($email),
            'purpose' => SenderCategory::Promo,
            'name' => fake()->company(),
            'domain' => substr(strrchr($email, '@'), 1),
            'message_count' => 6,
            'unread_count' => 5,
            'first_seen_at' => now()->subWeeks(3),
            'last_message_at' => now()->subDay(),
            'has_list_unsubscribe' => true,
            'list_unsubscribe_header' => '<https://example.com/unsub>',
            'list_unsubscribe_post' => 'List-Unsubscribe=One-Click',
            'category' => SenderCategory::Promo,
            'recommendation' => SenderRecommendation::Unsubscribe,
            'recommendation_reason' => 'Promotional mail with an unsubscribe header; you rarely open it.',
            'status' => SenderStatus::Pending,
            'gmail_categories' => ['CATEGORY_PROMOTIONS'],
        ];
    }

    public function keep(): static
    {
        return $this->state(fn () => [
            'has_list_unsubscribe' => false,
            'list_unsubscribe_header' => null,
            'list_unsubscribe_post' => null,
            'category' => SenderCategory::Person,
            'purpose' => SenderCategory::Person,
            'recommendation' => SenderRecommendation::Keep,
            'recommendation_reason' => 'Looks like a person, not a list.',
            'gmail_categories' => [],
            'message_count' => 2,
            'unread_count' => 1,
        ]);
    }

    public function digest(): static
    {
        return $this->state(fn () => [
            'category' => SenderCategory::Newsletter,
            'purpose' => SenderCategory::Newsletter,
            'recommendation' => SenderRecommendation::Digest,
            'recommendation_reason' => 'Recurring mail you can read later instead of in the inbox.',
        ]);
    }
}
