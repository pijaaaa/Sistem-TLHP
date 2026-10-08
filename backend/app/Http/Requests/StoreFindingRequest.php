<?php

namespace App\Http\Requests;

use App\Enums\FindingSource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFindingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'source' => ['nullable', Rule::enum(FindingSource::class)],
            'source_name' => ['nullable', 'string', 'max:255'],
            'lhp_number' => ['nullable', 'string', 'max:100'],
            'lhp_date' => ['nullable', 'date'],
            'finding_date' => ['nullable', 'date'],
            'response_period_start' => ['nullable', 'date'],
            'response_period_end' => ['nullable', 'date', 'after_or_equal:response_period_start'],
            'scope' => ['nullable', 'string'],
        ];
    }
}
