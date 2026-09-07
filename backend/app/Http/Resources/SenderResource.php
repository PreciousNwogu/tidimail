<?php

namespace App\Http\Resources;

use App\Models\Sender;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Sender */
class SenderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'account_id' => $this->account_id,
            'email' => $this->email,
            'name' => $this->displayName(),
            'domain' => $this->domain,
            'message_count' => $this->message_count,
            'unread_count' => $this->unread_count,
            'first_seen_at' => $this->first_seen_at?->toIso8601String(),
            'last_message_at' => $this->last_message_at?->toIso8601String(),
            'has_list_unsubscribe' => $this->has_list_unsubscribe,
            'category' => $this->category?->value,
            'purpose' => $this->purpose?->value ?? $this->category?->value,
            'recommendation' => $this->recommendation?->value,
            'recommendation_reason' => $this->recommendation_reason,
            'status' => $this->status?->value,
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'gmail_categories' => $this->gmail_categories ?? [],
            'sample_subjects' => $this->whenLoaded(
                'messages',
                fn () => $this->messages->take(3)->pluck('subject')->filter()->values()
            ),
            'messages' => MessageResource::collection($this->whenLoaded('messages')),
            'actions' => InboxActionResource::collection($this->whenLoaded('inboxActions')),
        ];
    }
}
