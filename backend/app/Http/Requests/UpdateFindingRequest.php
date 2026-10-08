<?php

namespace App\Http\Requests;

use App\Enums\FindingSource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFindingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'source' => ['sometimes', 'nullable', Rule::enum(FindingSource::class)],
            'source_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'lhp_number' => ['sometimes', 'nullable', 'string', 'max:100'],
            'lhp_date' => ['sometimes', 'nullable', 'date'],
            'finding_date' => ['sometimes', 'nullable', 'date'],
            'response_period_start' => ['sometimes', 'nullable', 'date'],
            'response_period_end' => ['sometimes', 'nullable', 'date', 'after_or_equal:response_period_start'],
            'scope' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
