<?php

namespace Tests\Feature;

use App\Jobs\SyncInboxJob;
use App\Models\Account;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DailySyncCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_daily_sync_queues_accounts_that_are_due(): void
    {
        Queue::fake();

        $due = Account::factory()->create([
            'last_synced_at' => now()->subDay(),
        ]);
        $fresh = Account::factory()->create([
            'last_synced_at' => now()->subHour(),
        ]);

        $this->artisan('tidimail:sync-inbox')->assertSuccessful();

        Queue::assertPushed(SyncInboxJob::class, fn (SyncInboxJob $job) => $job->account->is($due));
        Queue::assertNotPushed(SyncInboxJob::class, fn (SyncInboxJob $job) => $job->account->is($fresh));
    }

    public function test_force_syncs_accounts_that_already_ran_today(): void
    {
        Queue::fake();

        $fresh = Account::factory()->create([
            'last_synced_at' => now()->subHour(),
        ]);

        $this->artisan('tidimail:sync-inbox', ['--force' => true])->assertSuccessful();

        Queue::assertPushed(SyncInboxJob::class, fn (SyncInboxJob $job) => $job->account->is($fresh));
    }
}
