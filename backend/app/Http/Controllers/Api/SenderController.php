<?php

namespace App\Http\Controllers\Api;

use App\Enums\SenderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReviewSenderRequest;
use App\Http\Resources\InboxActionResource;
use App\Http\Resources\SenderResource;
use App\Models\Sender;
use App\Services\Inbox\SenderReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SenderController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $accountIds = $request->user()->accounts()->pluck('id');

        $query = Sender::query()
            ->whereIn('account_id', $accountIds)
            ->orderByDesc('message_count');

        if ($status = $request->query('status')) {
            $enum = SenderStatus::tryFrom($status);
            abort_unless($enum, 422, 'Invalid status filter.');
            $query->where('status', $enum);
        }

        if ($search = $request->query('q')) {
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('email', 'like', '%'.$search.'%')
                    ->orWhere('name', 'like', '%'.$search.'%')
                    ->orWhere('domain', 'like', '%'.$search.'%');
            });
        }

        return SenderResource::collection($query->paginate(25));
    }

    public function show(Request $request, Sender $sender): SenderResource
    {
        $this->authorizeSender($request, $sender);

        $sender->load([
            'messages' => fn ($query) => $query->orderByDesc('received_at')->limit(5),
            'inboxActions' => fn ($query) => $query->latest()->limit(10),
        ]);

        return new SenderResource($sender);
    }

    public function review(ReviewSenderRequest $request, Sender $sender, SenderReviewService $reviews): JsonResponse
    {
        $this->authorizeSender($request, $sender);

        $action = $reviews->review($sender, $request->action());

        return response()->json([
            'sender' => new SenderResource($sender->refresh()->load(
                ['messages' => fn ($query) => $query->orderByDesc('received_at')->limit(3)]
            )),
            'action' => new InboxActionResource($action),
        ]);
    }

    private function authorizeSender(Request $request, Sender $sender): void
    {
        $sender->loadMissing('account');

        abort_unless($sender->account && $sender->account->user_id === $request->user()->id, 404);
    }
}
