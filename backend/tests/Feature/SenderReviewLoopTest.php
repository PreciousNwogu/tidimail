<?php

namespace Tests\Feature;

use App\Enums\InboxActionStatus;
use App\Enums\SenderRecommendation;
use App\Enums\SenderStatus;
use App\Models\Account;
use App\Models\Message;
use App\Models\Sender;
use App\Models\User;
use App\Services\Inbox\InboxSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SenderReviewLoopTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_clusters_gmail_messages_into_senders_and_recommends_actions(): void
    {
        $account = Account::factory()->create();

        $this->gmail
            ->seedMessage('m-promo-1', [
                'From' => 'Deals <deals@shop.com>',
                'Subject' => '50% off everything',
                'List-Unsubscribe' => '<https://shop.com/unsub>',
                'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
            ], ['INBOX', 'UNREAD', 'CATEGORY_PROMOTIONS'])
            ->seedMessage('m-promo-2', [
                'From' => 'Deals <deals@shop.com>',
                'Subject' => 'Last chance sale',
                'List-Unsubscribe' => '<https://shop.com/unsub>',
            ], ['INBOX', 'UNREAD', 'CATEGORY_PROMOTIONS'])
            ->seedMessage('m-promo-3', [
                'From' => 'Deals <deals@shop.com>',
                'Subject' => 'Flash deal',
                'List-Unsubscribe' => '<https://shop.com/unsub>',
            ], ['INBOX', 'UNREAD', 'CATEGORY_PROMOTIONS'])
            ->seedMessage('m-person-1', [
                'From' => 'Jordan Lee <jordan@example.com>',
                'Subject' => 'Are you free Thursday?',
            ], ['INBOX', 'UNREAD']);

        app(InboxSyncService::class)->sync($account);

        $promo = Sender::query()->where('email', 'deals@shop.com')->first();
        $person = Sender::query()->where('email', 'jordan@example.com')->first();

        $this->assertNotNull($promo);
        $this->assertSame(3, $promo->message_count);
        $this->assertTrue($promo->has_list_unsubscribe);
        $this->assertSame(SenderRecommendation::Unsubscribe, $promo->recommendation);
        $this->assertSame(SenderStatus::Pending, $promo->status);

        $this->assertNotNull($person);
        $this->assertSame(SenderRecommendation::Keep, $person->recommendation);
        $this->assertSame(SenderStatus::Pending, $person->status);
    }

    public function test_sync_splits_amazon_promos_from_receipts_on_the_same_address(): void
    {
        $account = Account::factory()->create();

        $this->gmail
            ->seedMessage('amz-promo-1', [
                'From' => 'Amazon <auto-confirm@amazon.com>',
                'Subject' => 'Prime Day: 40% off deals',
                'List-Unsubscribe' => '<https://amazon.com/unsub>',
                'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
            ], ['INBOX', 'UNREAD', 'CATEGORY_PROMOTIONS'], 'Lightning deals on headphones.')
            ->seedMessage('amz-promo-2', [
                'From' => 'Amazon <auto-confirm@amazon.com>',
                'Subject' => 'Your exclusive offer expires tonight',
                'List-Unsubscribe' => '<https://amazon.com/unsub>',
            ], ['INBOX', 'UNREAD', 'CATEGORY_PROMOTIONS'], 'Save on Prime.')
            ->seedMessage('amz-promo-3', [
                'From' => 'Amazon <auto-confirm@amazon.com>',
                'Subject' => 'Lightning deal on kitchen gear',
                'List-Unsubscribe' => '<https://amazon.com/unsub>',
            ], ['INBOX', 'UNREAD', 'CATEGORY_PROMOTIONS'])
            ->seedMessage('amz-receipt-1', [
                'From' => 'Amazon <auto-confirm@amazon.com>',
                'Subject' => 'Your order has shipped',
            ], ['INBOX', 'UNREAD', 'CATEGORY_UPDATES'], 'Tracking for order #114-883.')
            ->seedMessage('amz-receipt-2', [
                'From' => 'Amazon <auto-confirm@amazon.com>',
                'Subject' => 'Your invoice for March',
            ], ['INBOX', 'CATEGORY_UPDATES'], 'Payment received for order #114-883.');

        app(InboxSyncService::class)->sync($account);

        $piles = Sender::query()->where('email', 'auto-confirm@amazon.com')->get();
        $this->assertCount(2, $piles);

        $promo = $piles->firstWhere('purpose', \App\Enums\SenderCategory::Promo);
        $receipt = $piles->firstWhere('purpose', \App\Enums\SenderCategory::Receipt);

        $this->assertNotNull($promo);
        $this->assertNotNull($receipt);
        $this->assertSame(3, $promo->message_count);
        $this->assertSame(2, $receipt->message_count);
        $this->assertSame(SenderRecommendation::Unsubscribe, $promo->recommendation);
        $this->assertSame(SenderRecommendation::Keep, $receipt->recommendation);
        $this->assertStringContainsString('receipt', strtolower($receipt->recommendation_reason));
        $this->assertStringContainsString('separate pile', strtolower($promo->recommendation_reason));
    }

    public function test_first_run_sweep_and_digest_review_can_be_undone(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create();
        $sender = Sender::factory()->digest()->for($account)->create([
            'email' => 'news@weekly.com',
            'name' => 'Weekly',
            'message_count' => 4,
        ]);
        Message::factory()->for($account)->for($sender)->count(4)->create([
            'is_in_inbox' => true,
            'label_ids' => ['INBOX', 'UNREAD'],
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/sweep')
            ->assertOk()
            ->assertJsonPath('mode', 'first_run')
            ->assertJsonPath('stats.senders_pending', 1)
            ->assertJsonPath('senders.0.email', 'news@weekly.com');

        $review = $this->postJson("/api/senders/{$sender->id}/review", ['action' => 'digest'])
            ->assertOk()
            ->assertJsonPath('sender.status', 'digest')
            ->assertJsonPath('action.can_undo', true);

        $this->assertFalse($sender->messages()->where('is_in_inbox', true)->exists());
        $this->assertSame(4, $sender->messages()->whereNotNull('purge_at')->count());
        $this->assertTrue($sender->messages()->first()?->purge_at?->isSameDay(now()->addDays(30)));
        $this->assertNotEmpty($this->gmail->batchModifies);
        $this->assertContains('INBOX', $this->gmail->batchModifies[0]['remove']);

        $actionId = $review->json('action.id');

        $this->postJson("/api/actions/{$actionId}/undo")
            ->assertOk()
            ->assertJsonPath('action.status', 'undone');

        $this->assertTrue($sender->fresh()->status === SenderStatus::Pending);
        $this->assertTrue($sender->messages()->where('is_in_inbox', true)->exists());
        $this->assertFalse($sender->messages()->whereNotNull('purge_at')->exists());
    }

    public function test_unsubscribe_requires_a_header_then_archives_and_records_proof(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create();
        $blocked = Sender::factory()->keep()->for($account)->create();
        $sender = Sender::factory()->for($account)->create([
            'email' => 'deals@shop.com',
            'has_list_unsubscribe' => true,
            'list_unsubscribe_header' => '<https://shop.com/unsub>',
            'list_unsubscribe_post' => 'List-Unsubscribe=One-Click',
            'recommendation' => SenderRecommendation::Unsubscribe,
        ]);
        Message::factory()->for($account)->for($sender)->create([
            'gmail_id' => 'promo-1',
            'is_in_inbox' => true,
        ]);

        Sanctum::actingAs($user);

        $this->postJson("/api/senders/{$blocked->id}/review", ['action' => 'unsubscribe'])
            ->assertUnprocessable();

        $this->postJson("/api/senders/{$sender->id}/review", ['action' => 'unsubscribe'])
            ->assertOk()
            ->assertJsonPath('sender.status', 'unsubscribed')
            ->assertJsonPath('action.status', 'confirmed');

        $this->assertSame('https://shop.com/unsub', $this->gmail->unsubscribes[0]['url']);
        $this->assertTrue($this->gmail->unsubscribes[0]['one_click']);
        $this->assertSame(InboxActionStatus::Confirmed, $sender->inboxActions()->first()->status);
        $this->assertNotContains('promo-1', $this->gmail->trashed);
        $this->assertFalse($sender->fresh()->trash_unsubscribed_immediately);
        $this->assertNotNull($sender->messages()->first()?->purge_at);
        $this->assertNull($sender->messages()->first()?->purged_at);
    }

    public function test_unsubscribe_trash_now_moves_mail_to_gmail_trash_immediately(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create();
        $sender = Sender::factory()->for($account)->create([
            'email' => 'deals@shop.com',
            'has_list_unsubscribe' => true,
            'list_unsubscribe_header' => '<https://shop.com/unsub>',
            'list_unsubscribe_post' => 'List-Unsubscribe=One-Click',
            'recommendation' => SenderRecommendation::Unsubscribe,
        ]);
        Message::factory()->for($account)->for($sender)->create([
            'gmail_id' => 'promo-now',
            'is_in_inbox' => true,
        ]);

        Sanctum::actingAs($user);

        $review = $this->postJson("/api/senders/{$sender->id}/review", [
            'action' => 'unsubscribe',
            'trash_now' => true,
        ])
            ->assertOk()
            ->assertJsonPath('sender.status', 'unsubscribed')
            ->assertJsonPath('action.trashed_now', true);

        $this->assertContains('promo-now', $this->gmail->trashed);
        $this->assertTrue($sender->fresh()->trash_unsubscribed_immediately);
        $this->assertNull($sender->messages()->first()?->purge_at);
        $this->assertNotNull($sender->messages()->first()?->purged_at);
        $this->assertFalse($sender->messages()->where('is_in_inbox', true)->exists());

        $actionId = $review->json('action.id');

        $this->postJson("/api/actions/{$actionId}/undo")
            ->assertOk()
            ->assertJsonPath('action.status', 'undone');

        $this->assertTrue($sender->fresh()->status === SenderStatus::Pending);
        $this->assertFalse($sender->fresh()->trash_unsubscribed_immediately);
        $this->assertTrue($sender->messages()->where('is_in_inbox', true)->exists());
        $this->assertNull($sender->messages()->first()?->purged_at);
        $this->assertContains('TRASH', $this->gmail->batchModifies[array_key_last($this->gmail->batchModifies)]['remove']);
    }

    public function test_digest_trash_now_moves_mail_to_gmail_trash_immediately(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create();
        $sender = Sender::factory()->digest()->for($account)->create();
        Message::factory()->for($account)->for($sender)->create([
            'gmail_id' => 'digest-now',
            'is_in_inbox' => true,
        ]);

        Sanctum::actingAs($user);

        $this->postJson("/api/senders/{$sender->id}/review", [
            'action' => 'digest',
            'trash_now' => true,
        ])
            ->assertOk()
            ->assertJsonPath('sender.status', 'digest')
            ->assertJsonPath('action.trashed_now', true);

        $this->assertContains('digest-now', $this->gmail->trashed);
        $this->assertTrue($sender->fresh()->trash_unsubscribed_immediately);
        $this->assertNull($sender->messages()->first()?->purge_at);
        $this->assertNotNull($sender->messages()->first()?->purged_at);
    }

    public function test_unsubscribed_sender_with_trash_now_auto_trashes_new_mail(): void
    {
        $account = Account::factory()->create();
        Sender::factory()->for($account)->create([
            'email' => 'deals@shop.com',
            'name' => 'Deals',
            'status' => SenderStatus::Unsubscribed,
            'trash_unsubscribed_immediately' => true,
            'has_list_unsubscribe' => true,
            'list_unsubscribe_header' => '<https://shop.com/unsub>',
        ]);

        $this->gmail->seedMessage('new-unsub', [
            'From' => 'Deals <deals@shop.com>',
            'Subject' => 'Still mailing you',
            'List-Unsubscribe' => '<https://shop.com/unsub>',
        ], ['INBOX', 'UNREAD', 'CATEGORY_PROMOTIONS']);

        app(InboxSyncService::class)->sync($account);

        $sender = Sender::query()->where('email', 'deals@shop.com')->first();

        $this->assertSame(SenderStatus::Unsubscribed, $sender->status);
        $this->assertContains('new-unsub', $this->gmail->trashed);
        $this->assertFalse($sender->messages()->where('is_in_inbox', true)->exists());
        $this->assertNotNull($sender->messages()->first()?->purged_at);
        $this->assertTrue((bool) $sender->inboxActions()->latest()->first()->metadata['trash_now']);
    }

    public function test_already_reviewed_senders_auto_digest_new_mail_on_sync(): void
    {
        $account = Account::factory()->create();
        Sender::factory()->digest()->for($account)->create([
            'email' => 'news@weekly.com',
            'status' => SenderStatus::Digest,
            'name' => 'Weekly',
        ]);

        $this->gmail->seedMessage('new-1', [
            'From' => 'Weekly <news@weekly.com>',
            'Subject' => 'Issue 48',
            'List-Unsubscribe' => '<https://weekly.example/unsub>',
        ], ['INBOX', 'UNREAD', 'CATEGORY_UPDATES']);

        app(InboxSyncService::class)->sync($account);

        $sender = Sender::query()->where('email', 'news@weekly.com')->first();

        $this->assertSame(SenderStatus::Digest, $sender->status);
        $this->assertFalse($sender->messages()->where('is_in_inbox', true)->exists());
        $this->assertNotEmpty($this->gmail->batchModifies);
        $this->assertTrue((bool) $sender->inboxActions()->latest()->first()->metadata['auto_applied']);
    }

    public function test_apply_recommendations_skips_keep_and_completes_first_run(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create();

        Sender::factory()->for($account)->create();
        Sender::factory()->digest()->for($account)->create();
        Sender::factory()->keep()->for($account)->create();

        Sender::query()->each(function (Sender $sender) use ($account) {
            Message::factory()->for($account)->for($sender)->create(['is_in_inbox' => true]);
        });

        Sanctum::actingAs($user);

        $this->postJson('/api/sweep/apply-recommendations', [
            'actions' => ['unsubscribe', 'digest'],
        ])
            ->assertOk()
            ->assertJsonPath('applied_count', 2);

        $this->assertSame(1, Sender::query()->where('status', SenderStatus::Pending)->count());
        $this->assertSame(1, Sender::query()->pending()->count());
        $this->assertSame(
            1,
            Sender::query()->pending()->where('recommendation', SenderRecommendation::Keep)->count()
        );

        $this->postJson('/api/sweep/complete')->assertOk();
        $this->assertNotNull($user->fresh()->first_sweep_completed_at);

        $this->getJson('/api/sweep')
            ->assertOk()
            ->assertJsonPath('mode', 'daily');
    }

    public function test_users_cannot_review_someone_elses_sender(): void
    {
        $user = User::factory()->create();
        $other = Sender::factory()->create();

        Sanctum::actingAs($user);

        $this->postJson("/api/senders/{$other->id}/review", ['action' => 'keep'])
            ->assertNotFound();
    }

    public function test_bulk_review_applies_one_action_to_selected_senders(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create();
        $digestOne = Sender::factory()->digest()->for($account)->create();
        $digestTwo = Sender::factory()->digest()->for($account)->create();
        $keep = Sender::factory()->keep()->for($account)->create();
        $foreign = Sender::factory()->create();

        foreach ([$digestOne, $digestTwo, $keep] as $sender) {
            Message::factory()->for($account)->for($sender)->create(['is_in_inbox' => true]);
        }

        Sanctum::actingAs($user);

        $this->getJson('/api/senders/pending-ids')
            ->assertOk()
            ->assertJsonPath('total', 3);

        $this->postJson('/api/senders/review-bulk', [
            'action' => 'digest',
            'sender_ids' => [$digestOne->id, $digestTwo->id, $foreign->id],
        ])
            ->assertOk()
            ->assertJsonPath('applied_count', 2);

        $this->assertSame(SenderStatus::Digest, $digestOne->fresh()->status);
        $this->assertSame(SenderStatus::Digest, $digestTwo->fresh()->status);
        $this->assertSame(SenderStatus::Pending, $keep->fresh()->status);
        $this->assertSame(SenderStatus::Pending, $foreign->fresh()->status);
    }

    public function test_bulk_unsubscribe_digests_senders_without_a_header(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create();
        $list = Sender::factory()->for($account)->create([
            'has_list_unsubscribe' => true,
            'list_unsubscribe_header' => '<https://shop.com/unsub>',
        ]);
        $noHeader = Sender::factory()->keep()->for($account)->create();

        Message::factory()->for($account)->for($list)->create(['is_in_inbox' => true]);
        Message::factory()->for($account)->for($noHeader)->create(['is_in_inbox' => true]);

        Sanctum::actingAs($user);

        $this->postJson('/api/senders/review-bulk', [
            'action' => 'unsubscribe',
            'sender_ids' => [$list->id, $noHeader->id],
        ])
            ->assertOk()
            ->assertJsonPath('applied_count', 2);

        $this->assertSame(SenderStatus::Unsubscribed, $list->fresh()->status);
        $this->assertSame(SenderStatus::Digest, $noHeader->fresh()->status);
    }

    public function test_bulk_trash_now_moves_mail_to_gmail_trash_immediately(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create();
        $list = Sender::factory()->for($account)->create([
            'has_list_unsubscribe' => true,
            'list_unsubscribe_header' => '<https://shop.com/unsub>',
        ]);
        $noHeader = Sender::factory()->keep()->for($account)->create();

        Message::factory()->for($account)->for($list)->create([
            'gmail_id' => 'bulk-now-1',
            'is_in_inbox' => true,
        ]);
        Message::factory()->for($account)->for($noHeader)->create([
            'gmail_id' => 'bulk-now-2',
            'is_in_inbox' => true,
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/senders/review-bulk', [
            'action' => 'unsubscribe',
            'trash_now' => true,
            'sender_ids' => [$list->id, $noHeader->id],
        ])
            ->assertOk()
            ->assertJsonPath('applied_count', 2)
            ->assertJsonPath('applied.0.trashed_now', true);

        $this->assertContains('bulk-now-1', $this->gmail->trashed);
        $this->assertContains('bulk-now-2', $this->gmail->trashed);
        $this->assertTrue($list->fresh()->trash_unsubscribed_immediately);
        $this->assertTrue($noHeader->fresh()->trash_unsubscribed_immediately);
        $this->assertSame(SenderStatus::Unsubscribed, $list->fresh()->status);
        $this->assertSame(SenderStatus::Digest, $noHeader->fresh()->status);
    }

    public function test_due_digest_mail_is_moved_to_gmail_trash(): void
    {
        $account = Account::factory()->create();
        $sender = Sender::factory()->digest()->for($account)->create([
            'status' => SenderStatus::Digest,
        ]);
        $message = Message::factory()->for($account)->for($sender)->create([
            'gmail_id' => 'old-digest',
            'is_in_inbox' => false,
            'purge_at' => now()->subMinute(),
        ]);

        $this->gmail->seedMessage('old-digest', [
            'From' => 'Weekly <news@weekly.com>',
            'Subject' => 'Issue 1',
        ], ['Label_digest']);

        $count = app(\App\Services\Inbox\MessagePurgeService::class)->purgeDue();

        $this->assertSame(1, $count);
        $this->assertContains('old-digest', $this->gmail->trashed);
        $this->assertNotNull($message->fresh()->purged_at);
    }

    public function test_keep_cancels_a_scheduled_purge(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create();
        $sender = Sender::factory()->digest()->for($account)->create();
        Message::factory()->for($account)->for($sender)->create([
            'is_in_inbox' => false,
            'purge_at' => now()->addDays(30),
        ]);

        Sanctum::actingAs($user);

        $this->postJson("/api/senders/{$sender->id}/review", ['action' => 'keep'])
            ->assertOk()
            ->assertJsonPath('sender.status', 'keep');

        $this->assertFalse($sender->messages()->whereNotNull('purge_at')->exists());
        $this->assertTrue($sender->messages()->where('is_in_inbox', true)->exists());
    }

    public function test_sync_stores_a_human_error_instead_of_google_json(): void
    {
        $account = Account::factory()->create();
        $this->gmail->failWith = new \RuntimeException(
            '{"error":{"code":401,"message":"Invalid Credentials","status":"UNAUTHENTICATED"}}'
        );

        try {
            app(InboxSyncService::class)->sync($account);
            $this->fail('Expected the scan to fail.');
        } catch (\Throwable $exception) {
            $this->assertStringContainsString('Reconnect Google', $exception->getMessage());
            $this->assertStringNotContainsString('Invalid Credentials', $exception->getMessage());
        }

        $this->assertSame('failed', $account->fresh()->sync_status);
        $this->assertSame(
            'Gmail access expired. Reconnect Google to continue.',
            $account->fresh()->sync_error
        );
    }

    public function test_keep_mail_is_not_moved_to_trash_even_if_purge_is_due(): void
    {
        $account = Account::factory()->create();
        $sender = Sender::factory()->keep()->for($account)->create();
        Message::factory()->for($account)->for($sender)->create([
            'gmail_id' => 'keep-me',
            'purge_at' => now()->subDay(),
        ]);

        $count = app(\App\Services\Inbox\MessagePurgeService::class)->purgeDue();

        $this->assertSame(0, $count);
        $this->assertNotContains('keep-me', $this->gmail->trashed);
    }
}
