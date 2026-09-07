<?php

namespace App\Console\Commands;

use App\Jobs\PurgeExpiredMailJob;
use Illuminate\Console\Command;

class PurgeExpiredMailCommand extends Command
{
    protected $signature = 'tidimail:purge-mail';

    protected $description = 'Move Digest and Unsubscribe mail to Gmail Trash after the retention period';

    public function handle(): int
    {
        PurgeExpiredMailJob::dispatch();
        $this->info('Queued mail purge.');

        return self::SUCCESS;
    }
}
