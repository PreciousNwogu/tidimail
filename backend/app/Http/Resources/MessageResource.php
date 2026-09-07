<?php

namespace App\Http\Resources;

use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Message */
class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'gmail_id' => $this->gmail_id,
            'subject' => $this->subject,
            'snippet' => $this->snippet,
            'received_at' => $this->received_at?->toIso8601String(),
            'is_read' => $this->is_read,
            'is_in_inbox' => $this->is_in_inbox,
            'purge_at' => $this->purge_at?->toIso8601String(),
            'purged_at' => $this->purged_at?->toIso8601String(),
        ];
    }
}
