<?php

namespace App\Http\Resources;

use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Account */
class AccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'provider' => $this->provider,
            'email' => $this->email,
            'name' => $this->name,
            'last_synced_at' => $this->last_synced_at?->toIso8601String(),
            'sync_status' => $this->sync_status,
            'sync_error' => $this->sync_error,
            'sync_scanned_count' => (int) ($this->sync_scanned_count ?? 0),
            'cleanup_pending_count' => (int) ($this->cleanup_pending_count ?? 0),
            'cleanup_alert_at' => $this->cleanup_alert_at?->toIso8601String(),
        ];
    }
}
