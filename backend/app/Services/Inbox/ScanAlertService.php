<?php

namespace App\Services\Inbox;

use App\Enums\SenderStatus;
use App\Models\Account;
use App\Models\PushSubscription;
use App\Models\Sender;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Throwable;

class ScanAlertService
{
    public function notifyAfterScan(Account $account): void
    {
        $account->loadMissing('user');
        $user = $account->user;

        if (! $user?->hasCompletedFirstSweep()) {
            return;
        }

        $pending = Sender::query()
            ->where('account_id', $account->id)
            ->where('status', SenderStatus::Pending)
            ->count();

        if ($pending === 0) {
            $account->forceFill([
                'cleanup_pending_count' => 0,
                'cleanup_alert_at' => null,
            ])->save();

            return;
        }

        $account->forceFill([
            'cleanup_pending_count' => $pending,
            'cleanup_alert_at' => now(),
        ])->save();

        $this->sendPush($account, $pending);
    }

    private function sendPush(Account $account, int $pending): void
    {
        $public = (string) config('tidimail.vapid.public_key');
        $private = (string) config('tidimail.vapid.private_key');

        if ($public === '' || $private === '') {
            return;
        }

        $subscriptions = $account->user?->pushSubscriptions ?? collect();
        if ($subscriptions->isEmpty()) {
            return;
        }

        $payload = json_encode([
            'title' => 'Tidimail is ready for cleanup',
            'body' => $pending === 1
                ? '1 new sender needs a decision. Open Tidimail to keep your inbox light.'
                : $pending.' new senders need a decision. Open Tidimail to keep your inbox light.',
            'url' => '/sweep',
        ], JSON_THROW_ON_ERROR);

        try {
            $webPush = new WebPush([
                'VAPID' => [
                    'subject' => (string) config('tidimail.vapid.subject'),
                    'publicKey' => $public,
                    'privateKey' => $private,
                ],
            ]);

            foreach ($subscriptions as $subscription) {
                $webPush->queueNotification(
                    Subscription::create([
                        'endpoint' => $subscription->endpoint,
                        'publicKey' => $subscription->public_key,
                        'authToken' => $subscription->auth_token,
                    ]),
                    $payload
                );
            }

            foreach ($webPush->flush() as $report) {
                if ($report->isSuccess()) {
                    continue;
                }

                $status = $report->getResponse()?->getStatusCode();
                if (in_array($status, [404, 410], true)) {
                    PushSubscription::query()->where('endpoint', $report->getEndpoint())->delete();
                }
            }
        } catch (Throwable $exception) {
            Log::warning('Cleanup push failed', [
                'account_id' => $account->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
