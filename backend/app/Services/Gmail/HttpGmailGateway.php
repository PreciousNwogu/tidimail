<?php

namespace App\Services\Gmail;

use App\Models\Account;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Http;

class HttpGmailGateway implements GmailGateway
{
    private const METADATA_HEADERS = [
        'From',
        'Subject',
        'Date',
        'List-Unsubscribe',
        'List-Unsubscribe-Post',
    ];

    public function getProfile(Account $account): array
    {
        $response = $this->gmail($account)->get('https://gmail.googleapis.com/gmail/v1/users/me/profile');

        $this->throwIfGmailFailed($response);

        return $response->json();
    }

    public function listRecentMessageIds(Account $account, int $maxResults = 100, ?string $pageToken = null, int $lookbackDays = 30, ?string $query = null): array
    {
        $response = $this->gmail($account)->get('https://gmail.googleapis.com/gmail/v1/users/me/messages', array_filter([
            'q' => $query ?? sprintf('newer_than:%dd', $lookbackDays),
            'maxResults' => $maxResults,
            'pageToken' => $pageToken,
        ]));

        $this->throwIfGmailFailed($response);

        $payload = $response->json();

        return [
            'ids' => collect($payload['messages'] ?? [])->pluck('id')->filter()->values()->all(),
            'nextPageToken' => $payload['nextPageToken'] ?? null,
            'resultSizeEstimate' => isset($payload['resultSizeEstimate']) ? (int) $payload['resultSizeEstimate'] : null,
        ];
    }

    public function getMessageMetadata(Account $account, string $gmailId): array
    {
        $response = $this->gmail($account)->get(
            'https://gmail.googleapis.com/gmail/v1/users/me/messages/'.$gmailId,
            [
                'format' => 'metadata',
                'metadataHeaders' => self::METADATA_HEADERS,
            ]
        );

        $this->throwIfGmailFailed($response);

        return $response->json();
    }

    public function getMessageMetadataMany(Account $account, array $gmailIds): array
    {
        $gmailIds = array_values(array_unique(array_filter($gmailIds)));

        if ($gmailIds === []) {
            return [];
        }

        $messages = [];

        foreach (array_chunk($gmailIds, 100) as $chunk) {
            $batched = $this->batchGetMetadata($account, $chunk);
            if ($batched !== []) {
                $messages += $batched;
                continue;
            }

            $messages += $this->poolGetMetadata($account, $chunk);
        }

        return $messages;
    }

    public function ensureLabel(Account $account, string $key, string $name): string
    {
        if ($account->labelId($key)) {
            return $account->labelId($key);
        }

        $response = $this->gmail($account)->get('https://gmail.googleapis.com/gmail/v1/users/me/labels');

        $this->throwIfGmailFailed($response);

        $existing = collect($response->json('labels') ?? [])->first(
            fn (array $label) => ($label['name'] ?? '') === $name
        );

        if ($existing) {
            $account->setLabelId($key, $existing['id']);
            $account->save();

            return $existing['id'];
        }

        $created = $this->gmail($account)->post('https://gmail.googleapis.com/gmail/v1/users/me/labels', [
            'name' => $name,
            'labelListVisibility' => 'labelShow',
            'messageListVisibility' => 'show',
        ]);

        $this->throwIfGmailFailed($created);

        $labelId = $created->json('id');
        $account->setLabelId($key, $labelId);
        $account->save();

        return $labelId;
    }

    public function batchModify(Account $account, array $gmailIds, array $addLabelIds = [], array $removeLabelIds = []): void
    {
        $gmailIds = array_values(array_unique(array_filter($gmailIds)));

        if ($gmailIds === []) {
            return;
        }

        foreach (array_chunk($gmailIds, 1000) as $chunk) {
            $response = $this->gmail($account)->post(
                'https://gmail.googleapis.com/gmail/v1/users/me/messages/batchModify',
                array_filter([
                    'ids' => $chunk,
                    'addLabelIds' => array_values($addLabelIds),
                    'removeLabelIds' => array_values($removeLabelIds),
                ], fn ($value) => $value !== [])
            );

            $this->throwIfGmailFailed($response);
        }
    }

    public function trashMessages(Account $account, array $gmailIds): void
    {
        $this->batchModify($account, $gmailIds, ['TRASH'], ['INBOX']);
    }

    public function postUnsubscribe(string $url, bool $oneClick = false): array
    {
        $request = Http::timeout(15)
            ->withHeaders(['User-Agent' => 'Tidimail/1.0'])
            ->withOptions(['allow_redirects' => ['max' => 3]]);

        $response = $oneClick
            ? $request->asForm()->post($url, ['List-Unsubscribe' => 'One-Click'])
            : $request->get($url);

        return [
            'url' => $url,
            'one_click' => $oneClick,
            'status' => $response->status(),
            'ok' => $response->successful(),
            'body_snippet' => mb_substr(strip_tags($response->body()), 0, 240),
        ];
    }

    /**
     * @param  list<string>  $gmailIds
     * @return array<string, array<string, mixed>>
     */
    protected function batchGetMetadata(Account $account, array $gmailIds): array
    {
        $this->refreshTokenIfNeeded($account);

        $boundary = 'batch_tidimail_'.bin2hex(random_bytes(6));
        $parts = '';

        foreach ($gmailIds as $gmailId) {
            $query = 'format=metadata';
            foreach (self::METADATA_HEADERS as $header) {
                $query .= '&metadataHeaders='.rawurlencode($header);
            }

            $parts .= "--{$boundary}\r\n";
            $parts .= "Content-Type: application/http\r\n";
            $parts .= 'Content-ID: <'.$gmailId.">\r\n\r\n";
            $parts .= 'GET /gmail/v1/users/me/messages/'.$gmailId.'?'.$query."\r\n\r\n";
        }

        $parts .= "--{$boundary}--\r\n";

        $response = Http::timeout(45)
            ->withToken($account->access_token)
            ->withBody($parts, 'multipart/mixed; boundary='.$boundary)
            ->post('https://www.googleapis.com/batch/gmail/v1');

        if (! $response->successful()) {
            return [];
        }

        return $this->parseBatchMetadata($response->body());
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected function parseBatchMetadata(string $body): array
    {
        $messages = [];

        foreach (preg_split('/\r?\n--/', $body) ?: [] as $part) {
            $start = strpos($part, '{');
            if ($start === false) {
                continue;
            }

            $decoded = json_decode(substr($part, $start), true);
            if (! is_array($decoded) || empty($decoded['id']) || empty($decoded['payload'])) {
                continue;
            }

            $messages[(string) $decoded['id']] = $decoded;
        }

        return $messages;
    }

    /**
     * @param  list<string>  $gmailIds
     * @return array<string, array<string, mixed>>
     */
    protected function poolGetMetadata(Account $account, array $gmailIds): array
    {
        $messages = [];

        foreach (array_chunk($gmailIds, 20) as $chunk) {
            $this->refreshTokenIfNeeded($account);

            $responses = Http::pool(function (Pool $pool) use ($account, $chunk) {
                foreach ($chunk as $gmailId) {
                    $pool->as($gmailId)
                        ->timeout(20)
                        ->withToken($account->access_token)
                        ->acceptJson()
                        ->get('https://gmail.googleapis.com/gmail/v1/users/me/messages/'.$gmailId, [
                            'format' => 'metadata',
                            'metadataHeaders' => self::METADATA_HEADERS,
                        ]);
                }
            });

            foreach ($responses as $gmailId => $response) {
                if ($response instanceof \Throwable) {
                    throw $response;
                }

                $this->throwIfGmailFailed($response);
                $messages[(string) $gmailId] = $response->json();
            }
        }

        return $messages;
    }

    protected function throwIfGmailFailed(\Illuminate\Http\Client\Response $response): void
    {
        if ($response->unauthorized()) {
            throw GmailException::unauthorized();
        }

        if ($response->forbidden() && str_contains((string) $response->body(), 'insufficient authentication scopes')) {
            throw GmailException::insufficientScopes();
        }

        if ($response->status() === 429) {
            throw GmailException::busy();
        }

        if ($response->serverError()) {
            throw GmailException::unavailable();
        }

        if (! $response->successful()) {
            throw GmailException::generic();
        }
    }

    protected function gmail(Account $account): PendingRequest
    {
        $this->refreshTokenIfNeeded($account);

        return Http::timeout(20)
            ->withToken($account->access_token)
            ->acceptJson();
    }

    protected function refreshTokenIfNeeded(Account $account): void
    {
        if ($account->token_expires_at && $account->token_expires_at->isFuture()) {
            return;
        }

        if (! $account->refresh_token) {
            throw GmailException::unauthorized();
        }

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'refresh_token' => $account->refresh_token,
            'grant_type' => 'refresh_token',
        ]);

        if (! $response->successful()) {
            throw GmailException::unauthorized();
        }

        $account->forceFill([
            'access_token' => $response->json('access_token'),
            'token_expires_at' => now()->addSeconds(((int) $response->json('expires_in', 3600)) - 60),
        ])->save();
    }
}
