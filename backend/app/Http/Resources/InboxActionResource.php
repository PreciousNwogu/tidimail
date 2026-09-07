<?php

namespace App\Http\Resources;

use App\Models\InboxAction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin InboxAction */
class InboxActionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type?->value,
            'status' => $this->status?->value,
            'sender' => new SenderResource($this->whenLoaded('sender')),
            'message_count' => count($this->gmail_message_ids ?? []),
            'can_undo' => $this->canBeUndone(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'undone_at' => $this->undone_at?->toIso8601String(),
            'proof' => $this->metadata['proof'] ?? null,
            'auto_applied' => (bool) ($this->metadata['auto_applied'] ?? false),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
