<?php

namespace App\Http\Requests;

use App\Enums\InboxActionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sender_ids' => ['required', 'array', 'min:1', 'max:50'],
            'sender_ids.*' => ['required', 'integer'],
            'action' => ['required', Rule::enum(InboxActionType::class)],
            'trash_now' => ['sometimes', 'boolean'],
        ];
    }

    public function action(): InboxActionType
    {
        return InboxActionType::from($this->validated('action'));
    }

    public function trashNow(): bool
    {
        return $this->boolean('trash_now')
            && in_array($this->action(), [InboxActionType::Unsubscribe, InboxActionType::Digest], true);
    }

    /**
     * @return list<int>
     */
    public function senderIds(): array
    {
        return array_values(array_unique(array_map('intval', $this->validated('sender_ids'))));
    }
}
