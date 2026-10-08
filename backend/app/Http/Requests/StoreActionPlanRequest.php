<?php

namespace App\Http\Requests;

use App\Enums\RiskLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreActionPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'finding_id' => ['required', 'integer', 'exists:findings,id'],
            'department_ids' => ['required', 'array', 'min:1'],
            'department_ids.*' => ['integer', 'exists:departments,id'],
            'title' => ['required', 'string', 'max:255'],
            'condition' => ['nullable', 'string'],
            'criteria' => ['nullable', 'string'],
            'cause' => ['nullable', 'string'],
            'impact' => ['nullable', 'string'],
            'risk' => ['nullable', Rule::enum(RiskLevel::class)],
            'deadline' => ['nullable', 'date'],
            'deadline_per_department' => ['nullable', 'array'],
            'deadline_per_department.*' => ['nullable', 'date'],
            'loss_idr' => ['nullable', 'numeric', 'min:0'],
            'loss_usd' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
