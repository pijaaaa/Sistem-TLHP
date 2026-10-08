<?php

namespace App\Http\Requests;

use App\Enums\CommentKind;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:2000'],
            'kind' => ['required', Rule::enum(CommentKind::class)],
        ];
    }
}