<?php

namespace App\Http\Controllers\Api;

use App\Enums\SenderRecommendation;
use App\Enums\SenderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ApplyRecommendationsRequest;
use App\Http\Resources\InboxActionResource;
use App\Http\Resources\SenderResource;
use App\Models\Message;
use App\Models\Sender;
use App\Services\Inbox\SenderReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SweepController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $accountIds = $user->accounts()->pluck('id');
        $firstRun = ! $user->hasCompletedFirstSweep();
        $limit = $firstRun
            ? (int) config('tidimail.sweep_first_run_limit', 20)
            : (int) config('tidimail.sweep_daily_limit', 8);

        $senders = Sender::query()
            ->whereIn('account_id', $accountIds)
            ->get();

        $pending = $senders
            ->where('status', SenderStatus::Pending)
            ->sortBy($this->rank(...))
            ->values();

        $messagesScanned = Message::query()->whereIn('account_id', $accountIds)->count();
        $stillInInbox = Message::query()->whereIn('account_id', $accountIds)->where('is_in_inbox', true)->count();
        $quietScore = $messagesScanned === 0
            ? 100
            : (int) round(100 * (1 - ($stillInInbox / $messagesScanned)));

        $needsYou = $pending
            ->filter(function (Sender $sender) {
                return $sender->recommendation === SenderRecommendation::Keep
                    && $sender->unread_count > 0
                    && $sender->last_message_at
                    && $sender->last_message_at->gte(now()->subDays(3));
            })
            ->values();

        return response()->json([
            'mode' => $firstRun ? 'first_run' : 'daily',
            'stats' => [
                'quiet_score' => $quietScore,
                'senders_total' => $senders->count(),
                'senders_pending' => $pending->count(),
                'messages_scanned' => $messagesScanned,
                'messages_in_inbox' => $stillInInbox,
                'recommended_unsubscribe' => $pending->where('recommendation', SenderRecommendation::Unsubscribe)->count(),
                'recommended_digest' => $pending->where('recommendation', SenderRecommendation::Digest)->count(),
                'recommended_keep' => $pending->where('recommendation', SenderRecommendation::Keep)->count(),
                'needs_you' => $needsYou->count(),
                'keep' => $senders->where('status', SenderStatus::Keep)->count(),
                'digest' => $senders->where('status', SenderStatus::Digest)->count(),
                'unsubscribed' => $senders->where('status', SenderStatus::Unsubscribed)->count(),
            ],
            'needs_you' => SenderResource::collection($this->withSamples($needsYou->take($limit))),
            'senders' => SenderResource::collection($this->withSamples($pending->take($limit))),
        ]);
    }

    public function complete(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->first_sweep_completed_at) {
            $user->forceFill(['first_sweep_completed_at' => now()])->save();
        }

        return response()->json([
            'first_sweep_completed_at' => $user->first_sweep_completed_at->toIso8601String(),
        ]);
    }

    public function applyRecommendations(ApplyRecommendationsRequest $request, SenderReviewService $reviews): JsonResponse
    {
        $accountIds = $request->user()->accounts()->pluck('id');
        $senders = Sender::query()
            ->whereIn('account_id', $accountIds)
            ->where('status', SenderStatus::Pending)
            ->limit(50)
            ->get();

        $result = $reviews->applyRecommendations($senders, $request->input('actions'));

        return response()->json([
            'applied' => InboxActionResource::collection(collect($result['applied'])),
            'applied_count' => count($result['applied']),
            'failed' => $result['failed'],
        ]);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Sender>|\Illuminate\Support\Enumerable  $senders
     * @return \Illuminate\Support\Collection<int, Sender>
     */
    private function withSamples($senders, int $limit = 3)
    {
        $senders = collect($senders);
        $ids = $senders->pluck('id')->filter();

        if ($ids->isEmpty()) {
            return $senders;
        }

        $bag = Message::query()
            ->whereIn('sender_id', $ids)
            ->orderByDesc('received_at')
            ->get()
            ->groupBy('sender_id');

        return $senders->each(function (Sender $sender) use ($bag, $limit) {
            $sender->setRelation(
                'messages',
                collect($bag->get($sender->id, []))->take($limit)->values()
            );
        });
    }

    private function rank(Sender $sender): string
    {
        $priority = match ($sender->recommendation) {
            SenderRecommendation::Unsubscribe => 1,
            SenderRecommendation::Digest => 2,
            SenderRecommendation::Keep => 3,
            default => 9,
        };

        return sprintf('%d-%010d', $priority, 1_000_000_000 - $sender->message_count);
    }
}
