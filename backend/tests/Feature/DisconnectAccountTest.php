<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Sender;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DisconnectAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_me_flags_gmail_reconnect_when_sync_error_says_so(): void
    {
        $account = Account::factory()->create([
            'sync_error' => 'Gmail access expired. Reconnect Google to continue.',
        ]);

        Sanctum::actingAs($account->user);

        $this->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('needs_gmail_reconnect', true);
    }

    public function test_disconnect_revokes_google_and_deletes_mail_data(): void
    {
        Http::fake([
            'oauth2.googleapis.com/revoke' => Http::response([], 200),
        ]);

        $account = Account::factory()->create();
        Sender::factory()->create(['account_id' => $account->id]);
        $user = $account->user;
        $token = $user->createToken('spa');

        $this->withToken($token->plainTextToken)
            ->postJson('/api/auth/disconnect')
            ->assertOk()
            ->assertJsonPath('message', 'Disconnected.');

        $this->assertDatabaseMissing('accounts', ['id' => $account->id]);
        $this->assertDatabaseMissing('senders', ['account_id' => $account->id]);
        $this->assertDatabaseHas('users', ['id' => $user->id]);
        $this->assertSame(0, $user->fresh()->tokens()->count());

        Http::assertSent(fn ($request) => str_contains($request->url(), 'oauth2.googleapis.com/revoke'));
    }

    public function test_delete_account_removes_the_user(): void
    {
        Http::fake([
            'oauth2.googleapis.com/revoke' => Http::response([], 200),
        ]);

        $account = Account::factory()->create();
        $user = $account->user;

        Sanctum::actingAs($user);

        $this->deleteJson('/api/me')
            ->assertOk()
            ->assertJsonPath('message', 'Account deleted.');

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('accounts', ['id' => $account->id]);
    }
}
