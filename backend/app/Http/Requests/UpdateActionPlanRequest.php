<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'description' => ['nullable', 'string'],
            'weight' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'status' => ['sometimes', 'in:draft,diajukan,disetujui,ditolak,revisi,menunggu_evidence,evidence_diajukan,evidence_disetujui,evidence_revisi'],
            'rejection_reason' => ['nullable', 'string'],
            'due_date' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Judul rencana aksi wajib diisi.',
            'weight.numeric' => 'Bobot harus berupa angka.',
            'weight.min' => 'Bobot minimal 0.',
            'weight.max' => 'Bobot maksimal 100.',
            'status.in' => 'Status tidak valid.',
        ];
    }
}
