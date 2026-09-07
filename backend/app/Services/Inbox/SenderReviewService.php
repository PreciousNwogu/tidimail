<?php

namespace App\Services\Inbox;

use App\Enums\InboxActionStatus;
use App\Enums\InboxActionType;
use App\Enums\SenderRecommendation;
use App\Enums\SenderStatus;
use App\Jobs\UnsubscribeJob;
use App\Models\InboxAction;
use App\Models\Sender;
use App\Services\Gmail\GmailGateway;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class SenderReviewService
{
    public function __construct(private GmailGateway $gmail)
    {
    }

    public function review(Sender $sender, InboxActionType $type): InboxAction
    {
        $sender->loadMissing('account.user');

        if ($type === InboxActionType::Unsubscribe && ! $sender->httpUnsubscribeUrl()) {
            throw ValidationException::withMessages([
                'action' => 'No unsubscribe header on this sender. Digest it instead.',
            ]);
        }

        $account = $sender->account;
        $alreadyUnsubscribed = $sender->status === SenderStatus::Unsubscribed;
        $targetMessages = $type === InboxActionType::Keep
            ? $sender->messages()->orderByDesc('received_at')->limit(100)->get()
            : $sender->messages()->where('is_in_inbox', true)->get();
        $previousLabels = $targetMessages->mapWithKeys(
            fn ($message) => [$message->gmail_id => $message->label_ids ?? []]
        )->all();

        $action = InboxAction::query()->create([
            'user_id' => $account->user_id,
            'account_id' => $account->id,
            'sender_id' => $sender->id,
            'type' => $type,
            'status' => $type === InboxActionType::Unsubscribe
                ? InboxActionStatus::Queued
                : InboxActionStatus::Applied,
            'gmail_message_ids' => $targetMessages->pluck('gmail_id')->all(),
            'previous_label_ids' => $previousLabels,
            'previous_sender_status' => $sender->status,
            'metadata' => [
                'unsubscribe_url' => $sender->httpUnsubscribeUrl(),
                'one_click' => $sender->supportsOneClickUnsubscribe(),
            ],
            'expires_at' => now()->addHours((int) config('tidimail.undo_hours', 24)),
        ]);

        $this->applyGmailChange($sender, $type, $targetMessages);

        $sender->forceFill([
            'status' => $this->statusFor($type),
            'reviewed_at' => now(),
        ])->save();

        if ($type === InboxActionType::Unsubscribe && ! $alreadyUnsubscribed) {
            UnsubscribeJob::dispatch($action);
        }

        return $action->refresh()->load('sender');
    }

    public function applyStatusToNewMail(Sender $sender): ?InboxAction
    {
        if (! in_array($sender->status, [SenderStatus::Digest, SenderStatus::Unsubscribed], true)) {
            return null;
        }

        $inboxMessages = $sender->messages()->where('is_in_inbox', true)->get();

        if ($inboxMessages->isEmpty()) {
            return null;
        }

        $type = $this->actionForStatus($sender->status);

        if (! $type) {
            return null;
        }

        $action = InboxAction::query()->create([
            'user_id' => $sender->account->user_id,
            'account_id' => $sender->account_id,
            'sender_id' => $sender->id,
            'type' => $type,
            'status' => InboxActionStatus::Applied,
            'gmail_message_ids' => $inboxMessages->pluck('gmail_id')->all(),
            'previous_label_ids' => $inboxMessages->mapWithKeys(
                fn ($message) => [$message->gmail_id => $message->label_ids ?? []]
            )->all(),
            'previous_sender_status' => $sender->status,
            'metadata' => ['auto_applied' => true],
            'expires_at' => now()->addHours((int) config('tidimail.undo_hours', 24)),
        ]);

        $this->applyGmailChange($sender, $type, $inboxMessages);

        return $action;
    }

    /**
     * @param  list<string>|null  $only
     * @return array{applied: list<InboxAction>, failed: list<array{sender_id: int, error: string}>}
     */
    public function applyRecommendations(Collection $senders, ?array $only = null): array
    {
        $allowed = $only
            ? array_map(fn (string $value) => SenderRecommendation::from($value), $only)
            : [SenderRecommendation::Unsubscribe, SenderRecommendation::Digest];

        $applied = [];
        $failed = [];

        foreach ($senders as $sender) {
            if ($sender->status !== SenderStatus::Pending) {
                continue;
            }

            if (! in_array($sender->recommendation, $allowed, true)) {
                continue;
            }

            $type = InboxActionType::from($sender->recommendation->value);

            try {
                if ($type === InboxActionType::Unsubscribe && ! $sender->httpUnsubscribeUrl()) {
                    $type = InboxActionType::Digest;
                }

                $applied[] = $this->review($sender, $type);
            } catch (\Throwable $exception) {
                $failed[] = [
                    'sender_id' => $sender->id,
                    'error' => $exception->getMessage(),
                ];
            }
        }

        return compact('applied', 'failed');
    }

    public function undo(InboxAction $action): InboxAction
    {
        if (! $action->canBeUndone()) {
            throw ValidationException::withMessages([
                'action' => 'This action can no longer be undone.',
            ]);
        }

        $action->loadMissing('account', 'sender');
        $account = $action->account;
        $ids = $action->gmail_message_ids ?? [];

        $add = ['INBOX'];
        $remove = array_filter([
            $account->labelId('digest'),
            $account->labelId('keep'),
            $account->labelId('unsubscribed'),
        ]);

        $this->gmail->batchModify($account, $ids, $add, array_values($remove));

        $action->sender?->messages()
            ->whereIn('gmail_id', $ids)
            ->update([
                'is_in_inbox' => true,
                'purge_at' => null,
                'purged_at' => null,
            ]);

        if ($action->sender && $action->previous_sender_status) {
            $action->sender->forceFill([
                'status' => $action->previous_sender_status,
                'reviewed_at' => $action->previous_sender_status === SenderStatus::Pending ? null : $action->sender->reviewed_at,
            ])->save();
        }

        $action->forceFill([
            'status' => InboxActionStatus::Undone,
            'undone_at' => now(),
        ])->save();

        return $action->refresh();
    }

    public function recordUnsubscribeProof(InboxAction $action, array $proof): InboxAction
    {
        $metadata = $action->metadata ?? [];
        $metadata['proof'] = $proof;

        $action->forceFill([
            'metadata' => $metadata,
            'status' => ($proof['ok'] ?? false)
                ? InboxActionStatus::Confirmed
                : InboxActionStatus::Failed,
        ])->save();

        return $action;
    }

    private function statusFor(InboxActionType $type): SenderStatus
    {
        return match ($type) {
            InboxActionType::Keep => SenderStatus::Keep,
            InboxActionType::Digest => SenderStatus::Digest,
            InboxActionType::Unsubscribe => SenderStatus::Unsubscribed,
        };
    }

    private function actionForStatus(SenderStatus $status): ?InboxActionType
    {
        return match ($status) {
            SenderStatus::Digest => InboxActionType::Digest,
            SenderStatus::Unsubscribed => InboxActionType::Unsubscribe,
            default => null,
        };
    }

    /**
     * @param  Collection<int, \App\Models\Message>  $messages
     */
    private function applyGmailChange(Sender $sender, InboxActionType $type, Collection $messages): void
    {
        $account = $sender->account;
        $ids = $messages->pluck('gmail_id')->all();
        $labels = config('tidimail.labels');

        if ($type === InboxActionType::Keep) {
            $keepId = $this->gmail->ensureLabel($account, 'keep', $labels['keep']);
            $remove = array_values(array_filter([
                $account->labelId('digest'),
                $account->labelId('unsubscribed'),
            ]));
            $this->gmail->batchModify($account, $ids, array_values(array_filter([$keepId, 'INBOX'])), $remove);
            $sender->messages()
                ->whereIn('gmail_id', $ids)
                ->update([
                    'is_in_inbox' => true,
                    'purge_at' => null,
                    'purged_at' => null,
                ]);

            return;
        }

        $labelKey = $type === InboxActionType::Digest ? 'digest' : 'unsubscribed';
        $labelId = $this->gmail->ensureLabel($account, $labelKey, $labels[$labelKey]);

        $this->gmail->batchModify($account, $ids, [$labelId], ['INBOX']);

        $sender->messages()
            ->whereIn('gmail_id', $ids)
            ->update([
                'is_in_inbox' => false,
                'purge_at' => now()->addDays((int) config('tidimail.delete_after_days', 30)),
                'purged_at' => null,
            ]);
    }
}
