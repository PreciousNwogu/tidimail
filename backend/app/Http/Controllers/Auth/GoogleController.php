<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\Response;

class GoogleController extends Controller
{
    public function redirect(): RedirectResponse
    {
        if (! $this->googleIsConfigured()) {
            return redirect($this->frontendUrl('/?error=google_not_configured'));
        }

        return Socialite::driver('google')
            ->stateless()
            ->scopes([
                'openid',
                'profile',
                'email',
                'https://www.googleapis.com/auth/gmail.modify',
            ])
            ->with([
                'access_type' => 'offline',
                'prompt' => 'consent',
            ])
            ->redirect();
    }

    public function callback(Request $request): Response
    {
        if (! $this->googleIsConfigured()) {
            return redirect($this->frontendUrl('/?error=google_not_configured'));
        }

        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (\Throwable $exception) {
            report($exception);

            return redirect($this->frontendUrl('/?error=google_failed'));
        }

        $approved = implode(' ', $googleUser->approvedScopes ?? []);
        if ($approved !== '' && ! str_contains($approved, 'gmail')) {
            return redirect($this->frontendUrl('/?error=google_missing_gmail_scope'));
        }

        $user = User::query()->where('google_id', $googleUser->getId())->first()
            ?? User::query()->where('email', $googleUser->getEmail())->first();

        if (! $user) {
            $user = User::query()->create([
                'name' => $googleUser->getName() ?: $googleUser->getEmail(),
                'email' => $googleUser->getEmail(),
                'google_id' => $googleUser->getId(),
                'email_verified_at' => now(),
                'password' => Str::random(40),
            ]);
        } else {
            $user->forceFill([
                'google_id' => $googleUser->getId(),
                'name' => $user->name ?: $googleUser->getName(),
            ])->save();
        }

        $account = Account::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'google_id' => $googleUser->getId(),
            ],
            [
                'provider' => 'gmail',
                'email' => $googleUser->getEmail(),
                'name' => $googleUser->getName(),
                'access_token' => $googleUser->token,
                'token_expires_at' => now()->addSeconds(((int) ($googleUser->expiresIn ?? 3600)) - 60),
                'sync_status' => 'idle',
                'sync_error' => null,
            ]
        );

        if ($googleUser->refreshToken) {
            $account->forceFill(['refresh_token' => $googleUser->refreshToken])->save();
        }

        $token = $user->createToken('spa')->plainTextToken;

        if ($request->expectsJson()) {
            return response()->json([
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ]);
        }

        $separator = str_contains((string) config('tidimail.frontend_url'), '?') ? '&' : '?';

        return redirect(config('tidimail.frontend_url').'/auth/callback'.$separator.'token='.urlencode($token));
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logged out.']);
    }

    public function disconnect(Request $request): JsonResponse
    {
        $user = $request->user();

        foreach ($user->accounts as $account) {
            $this->revokeGoogle($account);
            $account->delete();
        }

        $user->pushSubscriptions()->delete();
        $user->tokens()->delete();

        return response()->json(['message' => 'Disconnected.']);
    }

    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();

        foreach ($user->accounts as $account) {
            $this->revokeGoogle($account);
        }

        $user->tokens()->delete();
        $user->delete();

        return response()->json(['message' => 'Account deleted.']);
    }

    private function revokeGoogle(Account $account): void
    {
        $token = $account->refresh_token ?: $account->access_token;
        if (! $token) {
            return;
        }

        try {
            Http::asForm()->timeout(8)->post('https://oauth2.googleapis.com/revoke', [
                'token' => $token,
            ]);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    private function googleIsConfigured(): bool
    {
        if (app()->environment('testing')) {
            return true;
        }

        $id = (string) config('services.google.client_id');
        $secret = (string) config('services.google.client_secret');

        return $id !== ''
            && $secret !== ''
            && ! str_contains($id, 'your_google_client')
            && ! str_contains($secret, 'your_google_client');
    }

    private function frontendUrl(string $path): string
    {
        return rtrim((string) config('tidimail.frontend_url'), '/').$path;
    }
}
