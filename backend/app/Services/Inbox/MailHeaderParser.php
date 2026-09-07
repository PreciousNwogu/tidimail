<?php

namespace App\Services\Inbox;

use Carbon\Carbon;

class MailHeaderParser
{
    /**
     * @param  list<array{name?: string, value?: string}>  $headers
     */
    public function value(array $headers, string $name): ?string
    {
        foreach ($headers as $header) {
            if (strcasecmp((string) ($header['name'] ?? ''), $name) === 0) {
                return $header['value'] ?? null;
            }
        }

        return null;
    }

    /**
     * @return array{name: ?string, email: string}
     */
    public function parseFrom(string $from): array
    {
        $from = trim($from);

        if (preg_match('/^(?:"?([^"]*)"?\s*)?<([^>]+@[^>]+)>$/', $from, $matches)) {
            return [
                'name' => trim($matches[1]) !== '' ? trim($matches[1]) : null,
                'email' => strtolower(trim($matches[2])),
            ];
        }

        if (preg_match('/([^<\s]+@[^>\s]+)/', $from, $matches)) {
            return [
                'name' => null,
                'email' => strtolower(trim($matches[1])),
            ];
        }

        return [
            'name' => null,
            'email' => strtolower($from),
        ];
    }

    public function parseDate(?string $date, ?string $internalDateMs = null): Carbon
    {
        if ($internalDateMs) {
            return Carbon::createFromTimestampMs((int) $internalDateMs);
        }

        if ($date) {
            return Carbon::parse($date);
        }

        return now();
    }
}
