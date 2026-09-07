<?php

namespace App\Services\Inbox;

use App\Enums\SenderStatus;
use App\Models\Account;
use App\Models\Message;
use App\Services\Gmail\GmailGateway;
use Illuminate\Support\Facades\Log;

class MessagePurgeService
{
    public function __construct(private GmailGateway $gmail)
    {
    }

    public function purgeDue(): int
    {
        $due = Message::query()
            ->with('account')
            ->whereNotNull('purge_at')
            ->whereNull('purged_at')
            ->where('purge_at', '<=', now())
            ->whereHas('sender', fn ($query) => $query->whereIn('status', [
                SenderStatus::Digest,
                SenderStatus::Unsubscribed,
            ]))
            ->get()
            ->groupBy('account_id');

        $count = 0;

        foreach ($due as $messages) {
            $account = $messages->first()?->account;
            if (! $account instanceof Account) {
                continue;
            }

            $ids = $messages->pluck('gmail_id')->all();

            try {
                $this->gmail->trashMessages($account, $ids);
                Message::query()->whereIn('id', $messages->pluck('id'))->update(['purged_at' => now()]);
                $count += count($ids);
            } catch (\Throwable $exception) {
                Log::warning('Mail purge failed', [
                    'account_id' => $account->id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return $count;
    }
}
