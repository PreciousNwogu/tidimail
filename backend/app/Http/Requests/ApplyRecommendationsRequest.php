<?php

namespace App\Http\Requests;

use App\Enums\SenderRecommendation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApplyRecommendationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'actions' => ['sometimes', 'array', 'min:1'],
            'actions.*' => ['required', Rule::enum(SenderRecommendation::class)],
        ];
    }
}
