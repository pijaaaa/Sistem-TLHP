<?php

namespace App\Http\Requests;

use App\Enums\RiskLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateActionPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'condition' => ['sometimes', 'nullable', 'string'],
            'criteria' => ['sometimes', 'nullable', 'string'],
            'cause' => ['sometimes', 'nullable', 'string'],
            'impact' => ['sometimes', 'nullable', 'string'],
            'risk' => ['sometimes', 'nullable', Rule::enum(RiskLevel::class)],
            'deadline' => ['sometimes', 'nullable', 'date'],
            'loss_idr' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'loss_usd' => ['sometimes', 'nullable', 'numeric', 'min:0'],
        ];
    }
}
