<?php

namespace App\Services\Gmail;

use App\Models\Account;

class FakeGmailGateway implements GmailGateway
{
    /** @var array<string, array<string, mixed>> */
    public array $messages = [];

    /** @var list<array{ids: list<string>, add: list<string>, remove: list<string>}> */
    public array $batchModifies = [];

    /** @var array<string, string> */
    public array $labels = [
        'digest' => 'Label_digest',
        'keep' => 'Label_keep',
        'unsubscribed' => 'Label_unsubscribed',
    ];

    /** @var list<array{url: string, one_click: bool}> */
    public array $unsubscribes = [];

    /** @var list<string> */
    public array $trashed = [];

    public bool $unsubscribeSucceeds = true;

    public ?\Throwable $failWith = null;

    public function seedMessage(string $gmailId, array $headers, array $labelIds = ['INBOX', 'UNREAD'], ?string $snippet = null, ?int $internalDateMs = null): self
    {
        $payloadHeaders = [];
        foreach ($headers as $name => $value) {
            $payloadHeaders[] = ['name' => $name, 'value' => $value];
        }

        $this->messages[$gmailId] = [
            'id' => $gmailId,
            'threadId' => 'thread-'.$gmailId,
            'labelIds' => $labelIds,
            'snippet' => $snippet ?? ($headers['Subject'] ?? ''),
            'internalDate' => (string) ($internalDateMs ?? (now()->subDay()->getTimestamp() * 1000)),
            'payload' => ['headers' => $payloadHeaders],
        ];

        return $this;
    }

    public function getProfile(Account $account): array
    {
        if ($this->failWith) {
            throw $this->failWith;
        }

        return [
            'id' => $account->google_id,
            'emailAddress' => $account->email,
            'historyId' => '1',
        ];
    }

    public function listRecentMessageIds(Account $account, int $maxResults = 100, ?string $pageToken = null, int $lookbackDays = 30, ?string $query = null): array
    {
        return [
            'ids' => array_slice(array_keys($this->messages), 0, $maxResults),
            'nextPageToken' => null,
            'resultSizeEstimate' => count($this->messages),
        ];
    }

    public function getMessageMetadata(Account $account, string $gmailId): array
    {
        if (! isset($this->messages[$gmailId])) {
            throw GmailException::unauthorized();
        }

        return $this->messages[$gmailId];
    }

    public function getMessageMetadataMany(Account $account, array $gmailIds): array
    {
        $messages = [];

        foreach ($gmailIds as $gmailId) {
            $messages[$gmailId] = $this->getMessageMetadata($account, $gmailId);
        }

        return $messages;
    }

    public function ensureLabel(Account $account, string $key, string $name): string
    {
        $id = $this->labels[$key] ?? 'Label_'.$key;
        $account->setLabelId($key, $id);
        $account->save();

        return $id;
    }

    public function batchModify(Account $account, array $gmailIds, array $addLabelIds = [], array $removeLabelIds = []): void
    {
        $this->batchModifies[] = [
            'ids' => array_values($gmailIds),
            'add' => array_values($addLabelIds),
            'remove' => array_values($removeLabelIds),
        ];

        foreach ($gmailIds as $id) {
            if (! isset($this->messages[$id])) {
                continue;
            }

            $labels = $this->messages[$id]['labelIds'] ?? [];
            $labels = array_values(array_unique(array_merge($labels, $addLabelIds)));
            $labels = array_values(array_diff($labels, $removeLabelIds));
            $this->messages[$id]['labelIds'] = $labels;
        }
    }

    public function trashMessages(Account $account, array $gmailIds): void
    {
        $this->trashed = array_values(array_unique([...$this->trashed, ...$gmailIds]));
        $this->batchModify($account, $gmailIds, ['TRASH'], ['INBOX']);
    }

    public function postUnsubscribe(string $url, bool $oneClick = false): array
    {
        $this->unsubscribes[] = ['url' => $url, 'one_click' => $oneClick];

        return [
            'url' => $url,
            'one_click' => $oneClick,
            'status' => $this->unsubscribeSucceeds ? 200 : 502,
            'ok' => $this->unsubscribeSucceeds,
            'body_snippet' => $this->unsubscribeSucceeds ? 'unsubscribed' : 'failed',
        ];
    }
}
