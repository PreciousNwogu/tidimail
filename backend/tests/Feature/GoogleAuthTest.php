<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_google_redirect_requests_offline_gmail_access(): void
    {
        $driver = Mockery::mock();
        $driver->shouldReceive('stateless')->once()->andReturnSelf();
        $driver->shouldReceive('scopes')->once()->andReturnSelf();
        $driver->shouldReceive('with')->once()->with([
            'access_type' => 'offline',
            'prompt' => 'consent',
        ])->andReturnSelf();
        $driver->shouldReceive('redirect')->once()->andReturn(redirect('https://accounts.google.com'));

        Socialite::shouldReceive('driver')->with('google')->andReturn($driver);

        $this->get('/auth/google/redirect')->assertRedirect('https://accounts.google.com');
    }

    public function test_google_callback_persists_the_account_and_returns_a_token(): void
    {
        $googleUser = (new SocialiteUser)->map([
            'id' => 'google-99',
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
        ]);
        $googleUser->token = 'access-token';
        $googleUser->refreshToken = 'refresh-token';
        $googleUser->expiresIn = 3600;

        $driver = Mockery::mock();
        $driver->shouldReceive('stateless')->once()->andReturnSelf();
        $driver->shouldReceive('user')->once()->andReturn($googleUser);
        Socialite::shouldReceive('driver')->with('google')->andReturn($driver);

        $this->getJson('/auth/google/callback')
            ->assertOk()
            ->assertJsonPath('user.email', 'ada@example.com')
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email']]);

        $user = User::query()->where('email', 'ada@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('google-99', $user->google_id);
        $this->assertTrue($user->accounts()->where('email', 'ada@example.com')->exists());
    }

    public function test_google_callback_clears_sync_error_and_keeps_the_refresh_token(): void
    {
        $user = User::factory()->create([
            'email' => 'ada@example.com',
            'google_id' => 'google-99',
        ]);
        Account::factory()->for($user)->create([
            'google_id' => 'google-99',
            'email' => 'ada@example.com',
            'refresh_token' => 'keep-me',
            'sync_status' => 'failed',
            'sync_error' => 'Gmail access expired. Reconnect Google to continue.',
        ]);

        $googleUser = (new SocialiteUser)->map([
            'id' => 'google-99',
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
        ]);
        $googleUser->token = 'new-access-token';
        $googleUser->refreshToken = null;
        $googleUser->expiresIn = 3600;

        $driver = Mockery::mock();
        $driver->shouldReceive('stateless')->once()->andReturnSelf();
        $driver->shouldReceive('user')->once()->andReturn($googleUser);
        Socialite::shouldReceive('driver')->with('google')->andReturn($driver);

        $this->getJson('/auth/google/callback')->assertOk();

        $account = Account::query()->where('google_id', 'google-99')->first();
        $this->assertNotNull($account);
        $this->assertSame('keep-me', $account->refresh_token);
        $this->assertNull($account->sync_error);
        $this->assertSame('idle', $account->sync_status);
    }
}
