<?php

namespace App\Services;

use App\Enums\FindingStatus;
use App\Models\Finding;
use Illuminate\Validation\ValidationException;

class FindingService
{
    public function createDraft(array $data): Finding
    {
        $fiscal_year = $this->calculateFiscalYear($data['response_period_start'] ?? null);
        
        return Finding::create(array_merge($data, [
            'fiscal_year' => $fiscal_year,
            'status' => FindingStatus::Draft->value,
            'created_by' => auth()->id(),
        ]));
    }

    public function update(Finding $finding, array $data): Finding
    {
        if ($finding->status !== FindingStatus::Draft->value) {
            throw ValidationException::withMessages([
                'status' => 'Hanya temuan Draft yang dapat diubah.',
            ]);
        }

        if (isset($data['response_period_start'])) {
            $data['fiscal_year'] = $this->calculateFiscalYear($data['response_period_start']);
        }

        $finding->update($data);
        return $finding;
    }

    public function register(Finding $finding, array $departments): Finding
    {
        if ($finding->status !== FindingStatus::Draft->value) {
            throw ValidationException::withMessages([
                'status' => 'Hanya temuan Draft yang dapat didaftarkan.',
            ]);
        }

        if (empty($departments)) {
            throw ValidationException::withMessages([
                'departments' => 'Minimal satu departemen auditee harus dipilih.',
            ]);
        }

        if ($finding->documents()->count() === 0) {
            throw ValidationException::withMessages([
                'documents' => 'Minimal satu dokumen LHP harus dilampirkan.',
            ]);
        }

        $registration_number = FindingNumberService::generate($finding->fiscal_year);

        $finding->update([
            'registration_number' => $registration_number,
            'status' => FindingStatus::Terdaftar->value,
        ]);

        $finding->auditee_departments()->sync($departments);

        return $finding->refresh();
    }

    public function activate(Finding $finding): Finding
    {
        if ($finding->status !== FindingStatus::Terdaftar->value) {
            throw ValidationException::withMessages([
                'status' => 'Hanya temuan Terdaftar yang dapat diaktifkan.',
            ]);
        }

        $finding->update([
            'status' => FindingStatus::ProsessTindakLanjut->value,
            'activated_at' => now(),
        ]);

        return $finding->refresh();
    }

    private function calculateFiscalYear(?string $date): ?int
    {
        if (!$date) {
            return null;
        }
        
        return (int) \Carbon\Carbon::parse($date)->year;
    }
}
