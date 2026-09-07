<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    public function vapid(): JsonResponse
    {
        return response()->json([
            'public_key' => (string) config('tidimail.vapid.public_key'),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => ['required', 'string', 'max:2048'],
            'keys.p256dh' => ['required', 'string', 'max:255'],
            'keys.auth' => ['required', 'string', 'max:255'],
        ]);

        $subscription = PushSubscription::query()->updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'public_key' => $data['keys']['p256dh'],
            ],
            [
                'endpoint' => $data['endpoint'],
                'auth_token' => $data['keys']['auth'],
            ]
        );

        return response()->json(['id' => $subscription->id]);
    }
}
