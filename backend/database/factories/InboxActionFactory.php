<?php

namespace Database\Factories;

use App\Enums\InboxActionStatus;
use App\Enums\InboxActionType;
use App\Enums\SenderStatus;
use App\Models\Account;
use App\Models\InboxAction;
use App\Models\Sender;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InboxAction>
 */
class InboxActionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'account_id' => Account::factory(),
            'sender_id' => Sender::factory(),
            'type' => InboxActionType::Digest,
            'status' => InboxActionStatus::Applied,
            'gmail_message_ids' => ['msg-1'],
            'previous_label_ids' => ['msg-1' => ['INBOX', 'UNREAD']],
            'previous_sender_status' => SenderStatus::Pending,
            'metadata' => [],
            'expires_at' => now()->addHours(24),
        ];
    }
}
