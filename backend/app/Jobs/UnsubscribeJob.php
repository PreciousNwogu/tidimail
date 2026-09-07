<?php

namespace App\Jobs;

use App\Enums\InboxActionStatus;
use App\Models\InboxAction;
use App\Services\Gmail\GmailGateway;
use App\Services\Inbox\SenderReviewService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UnsubscribeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public InboxAction $action)
    {
    }

    public function handle(GmailGateway $gmail, SenderReviewService $reviews): void
    {
        $action = $this->action->fresh(['sender']);

        if (! $action || $action->status === InboxActionStatus::Undone) {
            return;
        }

        $url = $action->sender?->httpUnsubscribeUrl()
            ?? ($action->metadata['unsubscribe_url'] ?? null);

        if (! $url) {
            $reviews->recordUnsubscribeProof($action, [
                'ok' => false,
                'status' => 0,
                'body_snippet' => 'No unsubscribe URL',
            ]);

            return;
        }

        $proof = $gmail->postUnsubscribe($url, (bool) ($action->metadata['one_click'] ?? false));
        $reviews->recordUnsubscribeProof($action, $proof);
    }
}
