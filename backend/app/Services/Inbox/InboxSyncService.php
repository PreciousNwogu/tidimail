<?php

namespace App\Services\Inbox;

use App\Enums\SenderStatus;
use App\Models\Account;
use App\Models\Message;
use App\Models\Sender;
use App\Services\Gmail\GmailException;
use App\Services\Gmail\GmailGateway;
use Illuminate\Support\Facades\Log;
use Throwable;

class InboxSyncService
{
    public function __construct(
        private GmailGateway $gmail,
        private MailHeaderParser $parser,
        private SenderClassifier $classifier,
        private SenderReviewService $reviews,
    ) {
    }

    public function sync(Account $account): Account
    {
        set_time_limit(900);

        $account->forceFill([
            'sync_status' => 'running',
            'sync_error' => null,
            'sync_scanned_count' => 0,
        ])->save();

        try {
            $profile = $this->gmail->getProfile($account);
            if (! empty($profile['historyId'])) {
                $account->last_history_id = (string) $profile['historyId'];
            }

            $touchedSenderIds = $this->ingestMessages($account);
            $this->refreshSenders($account, $touchedSenderIds);
            $this->applyExistingDecisions($account, $touchedSenderIds);

            $account->forceFill([
                'sync_status' => 'idle',
                'last_synced_at' => $account->last_synced_at ?? now(),
                'sync_error' => null,
            ])->save();
        } catch (Throwable $exception) {
            $friendly = GmailException::sanitize($exception);

            $account->forceFill([
                'sync_status' => 'failed',
                'sync_error' => $friendly->getMessage(),
            ])->save();

            Log::warning('Inbox sync failed', [
                'account_id' => $account->id,
                'error' => $exception->getMessage(),
            ]);

            throw $friendly;
        }

        return $account->refresh();
    }

    /**
     * @return list<int>
     */
    private function ingestMessages(Account $account): array
    {
        $lookbackDays = (int) config('tidimail.sync_lookback_days', 30);
        $max = (int) config('tidimail.sync_max_messages', 5000);
        $touched = [];
        $scanned = 0;

        $queries = [
            'clutter' => sprintf(
                'in:inbox newer_than:%dd (category:promotions OR category:social OR category:updates OR category:forums)',
                $lookbackDays
            ),
            'rest' => sprintf(
                'in:inbox newer_than:%dd -category:promotions -category:social -category:updates -category:forums',
                $lookbackDays
            ),
        ];

        foreach ($queries as $phase => $query) {
            $pageToken = null;

            while ($scanned < $max) {
                $page = $this->gmail->listRecentMessageIds(
                    $account,
                    min(100, $max - $scanned),
                    $pageToken,
                    $lookbackDays,
                    $query
                );
                $ids = $page['ids'];

                if ($ids === []) {
                    break;
                }

                $alreadyHave = Message::query()
                    ->where('account_id', $account->id)
                    ->whereIn('gmail_id', $ids)
                    ->pluck('gmail_id')
                    ->all();
                $freshIds = array_values(array_diff($ids, $alreadyHave));

                foreach ($this->gmail->getMessageMetadataMany($account, $freshIds) as $gmailId => $raw) {
                    $sender = $this->upsertMessage($account, (string) $gmailId, $raw);
                    if ($sender) {
                        $touched[$sender->id] = $sender->id;
                    }
                }

                $scanned += count($ids);
                $account->forceFill(['sync_scanned_count' => $scanned])->save();

                if ($touched !== []) {
                    $this->refreshSenders($account, array_values($touched));
                }

                $pageToken = $page['nextPageToken'] ?? null;
                if (! $pageToken) {
                    break;
                }
            }
        }

        return array_values($touched);
    }

    private function upsertMessage(Account $account, string $gmailId, ?array $raw = null): ?Sender
    {
        $raw ??= $this->gmail->getMessageMetadata($account, $gmailId);
        $headers = $raw['payload']['headers'] ?? [];
        $from = $this->parser->value($headers, 'From');

        if (! $from) {
            return null;
        }

        $parsed = $this->parser->parseFrom($from);
        $email = $parsed['email'];
        $domain = str_contains($email, '@') ? substr(strrchr($email, '@'), 1) : null;
        $receivedAt = $this->parser->parseDate(
            $this->parser->value($headers, 'Date'),
            $raw['internalDate'] ?? null
        );
        $labelIds = $raw['labelIds'] ?? [];
        $listUnsubscribe = $this->parser->value($headers, 'List-Unsubscribe');
        $listUnsubscribePost = $this->parser->value($headers, 'List-Unsubscribe-Post');
        $subject = $this->parser->value($headers, 'Subject');
        $snippet = $raw['snippet'] ?? null;

        $purpose = $this->classifier->classifyMessage([
            'subject' => $subject,
            'snippet' => $snippet,
            'email' => $email,
            'name' => $parsed['name'],
            'label_ids' => $labelIds,
            'list_unsubscribe' => $listUnsubscribe,
        ]);

        $sender = Sender::query()->firstOrCreate(
            [
                'account_id' => $account->id,
                'email' => $email,
                'purpose' => $purpose->value,
            ],
            [
                'name' => $parsed['name'],
                'domain' => $domain,
                'first_seen_at' => $receivedAt,
                'status' => SenderStatus::Pending,
                'category' => $purpose,
            ]
        );

        if ($parsed['name'] && $sender->name !== $parsed['name']) {
            $sender->name = $parsed['name'];
        }

        if ($listUnsubscribe) {
            $sender->has_list_unsubscribe = true;
            $sender->list_unsubscribe_header = $listUnsubscribe;
            $sender->list_unsubscribe_post = $listUnsubscribePost;
        }

        $sender->save();

        Message::query()->updateOrCreate(
            [
                'account_id' => $account->id,
                'gmail_id' => $gmailId,
            ],
            [
                'sender_id' => $sender->id,
                'thread_id' => $raw['threadId'] ?? null,
                'subject' => $subject,
                'snippet' => $snippet,
                'category' => $purpose,
                'received_at' => $receivedAt,
                'is_read' => ! in_array('UNREAD', $labelIds, true),
                'is_in_inbox' => in_array('INBOX', $labelIds, true),
                'label_ids' => $labelIds,
                'list_unsubscribe' => $listUnsubscribe,
            ]
        );

        return $sender;
    }

    /**
     * @param  list<int>  $senderIds
     */
    private function refreshSenders(Account $account, array $senderIds): void
    {
        if ($senderIds === []) {
            return;
        }

        $senders = Sender::query()
            ->where('account_id', $account->id)
            ->whereIn('id', $senderIds)
            ->get();

        $messagesBySender = Message::query()
            ->where('account_id', $account->id)
            ->whereIn('sender_id', $senderIds)
            ->orderByDesc('received_at')
            ->get()
            ->groupBy('sender_id');

        foreach ($senders as $sender) {
            $messages = collect($messagesBySender->get($sender->id, []));

            $sender->message_count = $messages->count();
            $sender->unread_count = $messages->where('is_read', false)->count();
            $sender->first_seen_at = $messages->min('received_at') ?? $sender->first_seen_at;
            $sender->last_message_at = $messages->max('received_at') ?? $sender->last_message_at;

            if ($sender->status === SenderStatus::Pending) {
                $result = $this->classifier->classify($sender, $messages);
                $siblings = Sender::query()
                    ->where('account_id', $account->id)
                    ->where('email', $sender->email)
                    ->count();
                if ($siblings > 1) {
                    $result = $this->classifier->splitReason($result);
                }
                $sender->category = $result->category;
                $sender->recommendation = $result->recommendation;
                $sender->recommendation_reason = $result->reason;
                $sender->gmail_categories = $result->gmailCategories;
            }

            $sender->save();
        }
    }

    /**
     * @param  list<int>  $senderIds
     */
    private function applyExistingDecisions(Account $account, array $senderIds): void
    {
        if ($senderIds === []) {
            return;
        }

        $senders = Sender::query()
            ->where('account_id', $account->id)
            ->whereIn('id', $senderIds)
            ->whereIn('status', [SenderStatus::Digest, SenderStatus::Unsubscribed])
            ->get();

        foreach ($senders as $sender) {
            $this->reviews->applyStatusToNewMail($sender);
        }
    }
}
