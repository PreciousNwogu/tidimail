<?php

namespace App\Services\Gmail;

use App\Models\Account;

interface GmailGateway
{
    /**
     * @return array{id: string, email: string, historyId?: string}
     */
    public function getProfile(Account $account): array;

    /**
     * @return array{ids: list<string>, nextPageToken: ?string, resultSizeEstimate?: int|null}
     */
    public function listRecentMessageIds(Account $account, int $maxResults = 100, ?string $pageToken = null, int $lookbackDays = 30, ?string $query = null): array;

    /**
     * @return array<string, mixed>
     */
    public function getMessageMetadata(Account $account, string $gmailId): array;

    /**
     * @param  list<string>  $gmailIds
     * @return array<string, array<string, mixed>>
     */
    public function getMessageMetadataMany(Account $account, array $gmailIds): array;

    public function ensureLabel(Account $account, string $key, string $name): string;

    /**
     * @param  list<string>  $gmailIds
     * @param  list<string>  $addLabelIds
     * @param  list<string>  $removeLabelIds
     */
    public function batchModify(Account $account, array $gmailIds, array $addLabelIds = [], array $removeLabelIds = []): void;

    /**
     * @param  list<string>  $gmailIds
     */
    public function trashMessages(Account $account, array $gmailIds): void;

    public function postUnsubscribe(string $url, bool $oneClick = false): array;
}
