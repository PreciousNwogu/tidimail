<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AccountResource;
use App\Jobs\SyncInboxJob;
use App\Models\Account;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AccountController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return AccountResource::collection(
            $request->user()->accounts()->orderBy('email')->get()
        );
    }

    public function sync(Request $request, Account $account): JsonResponse
    {
        abort_unless($account->user_id === $request->user()->id, 404);

        $account->forceFill([
            'sync_status' => 'queued',
            'sync_error' => null,
        ])->save();

        SyncInboxJob::dispatch($account);

        return response()->json([
            'message' => 'Inbox sync queued.',
            'account' => new AccountResource($account->refresh()),
        ]);
    }
}
