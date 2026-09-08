<?php

namespace App\Http\Controllers\Api;

use App\Enums\SenderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\BulkReviewRequest;
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

        $perPage = min(100, max(1, (int) $request->query('per_page', 25)));

        return SenderResource::collection($query->paginate($perPage));
    }

    public function pendingIds(Request $request): JsonResponse
    {
        $accountIds = $request->user()->accounts()->pluck('id');
        $ids = Sender::query()
            ->whereIn('account_id', $accountIds)
            ->where('status', SenderStatus::Pending)
            ->orderByDesc('message_count')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();

        return response()->json([
            'ids' => $ids,
            'total' => $ids->count(),
        ]);
    }

    public function reviewBulk(BulkReviewRequest $request, SenderReviewService $reviews): JsonResponse
    {
        $accountIds = $request->user()->accounts()->pluck('id');
        $ids = $request->senderIds();
        $found = Sender::query()
            ->with('account.user')
            ->whereIn('account_id', $accountIds)
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        $senders = collect($ids)
            ->map(fn (int $id) => $found->get($id))
            ->filter();

        $result = $reviews->reviewMany($senders, $request->action(), $request->trashNow());

        return response()->json([
            'applied' => InboxActionResource::collection(collect($result['applied'])),
            'applied_count' => count($result['applied']),
            'failed' => $result['failed'],
        ]);
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

        $action = $reviews->review($sender, $request->action(), $request->trashNow());

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
