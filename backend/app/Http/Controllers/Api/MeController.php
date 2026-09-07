<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AccountResource;
use App\Models\Sender;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $accounts = $user->accounts()->orderBy('email')->get();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'first_sweep_completed_at' => $user->first_sweep_completed_at?->toIso8601String(),
            ],
            'accounts' => AccountResource::collection($accounts),
            'has_completed_first_sweep' => $user->hasCompletedFirstSweep(),
            'pending_senders' => Sender::query()
                ->whereIn('account_id', $accounts->pluck('id'))
                ->pending()
                ->count(),
            'purge_after_days' => (int) config('tidimail.delete_after_days', 30),
            'vapid_public_key' => (string) config('tidimail.vapid.public_key'),
            'needs_gmail_reconnect' => $accounts->contains(function ($account) {
                $expired = is_string($account->sync_error) && str_contains($account->sync_error, 'Reconnect Google');

                return $expired || blank($account->refresh_token);
            }),
        ]);
    }
}
