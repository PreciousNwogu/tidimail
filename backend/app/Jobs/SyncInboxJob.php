<?php

namespace App\Jobs;

use App\Models\Account;
use App\Services\Inbox\InboxSyncService;
use App\Services\Inbox\ScanAlertService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncInboxJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 900;

    public int $uniqueFor = 180;

    public function __construct(public Account $account, public bool $continue = false)
    {
    }

    public function uniqueId(): string
    {
        return $this->continue
            ? 'sync-account-'.$this->account->id.'-more'
            : 'sync-account-'.$this->account->id;
    }

    public function handle(InboxSyncService $sync, ScanAlertService $alerts): void
    {
        $account = $sync->sync($this->account, ! $this->continue);

        if ($account->sync_phase) {
            self::dispatch($account, true)->delay(now()->addSeconds(2));

            return;
        }

        $alerts->notifyAfterScan($account->fresh());
    }
}
