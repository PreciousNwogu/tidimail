<?php

namespace Tests\Feature;

use App\Jobs\SyncInboxJob;
use App\Models\Account;
use App\Models\User;
use App\Services\Inbox\InboxSyncService;
use App\Services\Inbox\ScanAlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScanAlertTest extends TestCase
{
    use RefreshDatabase;

    public function test_daily_scan_sets_a_cleanup_alert_when_senders_need_review(): void
    {
        $user = User::factory()->create([
            'first_sweep_completed_at' => now()->subDay(),
        ]);
        $account = Account::factory()->for($user)->create();

        $this->gmail->seedMessage('m-promo-1', [
            'From' => 'Deals <deals@shop.com>',
            'Subject' => '50% off everything',
            'List-Unsubscribe' => '<https://shop.com/unsub>',
        ], ['INBOX', 'UNREAD', 'CATEGORY_PROMOTIONS']);

        app(InboxSyncService::class)->sync($account);
        app(ScanAlertService::class)->notifyAfterScan($account->fresh());

        $account->refresh();
        $this->assertGreaterThan(0, $account->cleanup_pending_count);
        $this->assertNotNull($account->cleanup_alert_at);
    }

    public function test_first_run_scan_does_not_alert_before_the_user_finishes_onboarding(): void
    {
        $account = Account::factory()->create();

        $this->gmail->seedMessage('m-promo-1', [
            'From' => 'Deals <deals@shop.com>',
            'Subject' => '50% off everything',
            'List-Unsubscribe' => '<https://shop.com/unsub>',
        ], ['INBOX', 'UNREAD', 'CATEGORY_PROMOTIONS']);

        SyncInboxJob::dispatchSync($account);

        $this->assertNull($account->fresh()->cleanup_alert_at);
    }
}
