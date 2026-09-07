<?php

namespace App\Console\Commands;

use App\Jobs\SyncInboxJob;
use App\Models\Account;
use Illuminate\Console\Command;

class SyncInboxCommand extends Command
{
    protected $signature = 'tidimail:sync-inbox {--account= : Sync a single account id} {--force : Sync even if it already ran today}';

    protected $description = 'Queue a Gmail scan for connected accounts without asking the user';

    public function handle(): int
    {
        $skipHours = (int) config('tidimail.daily_sync_skip_hours', 18);

        $query = Account::query()
            ->whereNotNull('refresh_token')
            ->when(
                $this->option('account'),
                fn ($builder, $id) => $builder->whereKey($id)
            )
            ->when(
                ! $this->option('force'),
                fn ($builder) => $builder->where(function ($inner) use ($skipHours) {
                    $inner->whereNull('last_synced_at')
                        ->orWhere('last_synced_at', '<', now()->subHours($skipHours));
                })
            );

        $count = 0;

        $query->each(function (Account $account) use (&$count) {
            SyncInboxJob::dispatch($account);
            $count++;
            $this->info("Queued daily scan for {$account->email}");
        });

        if ($count === 0) {
            $this->info('No accounts need a scan right now.');
        }

        return self::SUCCESS;
    }
}
