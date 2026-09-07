<?php

namespace App\Http\Requests;

use App\Enums\InboxActionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewSenderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', Rule::enum(InboxActionType::class)],
        ];
    }

    public function action(): InboxActionType
    {
        return InboxActionType::from($this->validated('action'));
    }
}
