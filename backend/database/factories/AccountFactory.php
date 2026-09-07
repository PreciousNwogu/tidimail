<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'provider' => 'gmail',
            'google_id' => (string) fake()->unique()->numerify('##########'),
            'email' => fake()->unique()->safeEmail(),
            'name' => fake()->name(),
            'access_token' => 'test-access-token',
            'refresh_token' => 'test-refresh-token',
            'token_expires_at' => now()->addHour(),
            'sync_status' => 'idle',
            'gmail_label_ids' => [],
        ];
    }
}
