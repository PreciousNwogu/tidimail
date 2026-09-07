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

    public int $uniqueFor = 900;

    public function __construct(public Account $account)
    {
    }

    public function uniqueId(): string
    {
        return 'sync-account-'.$this->account->id;
    }

    public function handle(InboxSyncService $sync, ScanAlertService $alerts): void
    {
        $sync->sync($this->account);
        $alerts->notifyAfterScan($this->account->fresh());
    }
}
