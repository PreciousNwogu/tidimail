<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\InboxActionResource;
use App\Models\InboxAction;
use App\Services\Inbox\SenderReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InboxActionController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $actions = $request->user()
            ->inboxActions()
            ->with('sender')
            ->latest()
            ->limit(50)
            ->get();

        return InboxActionResource::collection($actions);
    }

    public function undo(Request $request, InboxAction $action, SenderReviewService $reviews): JsonResponse
    {
        abort_unless($action->user_id === $request->user()->id, 404);

        $action = $reviews->undo($action);

        return response()->json([
            'action' => new InboxActionResource($action->load('sender')),
        ]);
    }
}
