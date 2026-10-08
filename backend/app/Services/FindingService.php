<?php

namespace App\Services;

use App\Enums\FindingSource;
use App\Enums\FindingStatus;
use App\Events\FindingActivated;
use App\Events\FindingRegistered;
use App\Models\Department;
use App\Models\Finding;
use App\Support\AuditLogger;
use Illuminate\Validation\ValidationException;

class FindingService
{
    private const REGISTER_REQUIRED = [
        'title',
        'source',
        'lhp_number',
        'lhp_date',
        'finding_date',
        'response_period_start',
        'response_period_end',
        'scope',
    ];

    /** Setelah terdaftar hanya field deskriptif yang boleh diubah. */
    private const POST_REGISTER_FIELDS = ['title', 'scope', 'source_name'];

    public function createDraft(array $data): Finding
    {
        $finding = Finding::create(array_merge($data, [
            'fiscal_year' => $this->calculateFiscalYear($data['response_period_start'] ?? null),
            'status' => FindingStatus::Draft,
            'created_by' => auth()->id(),
        ]));

        $this->audit('finding.created', $finding, 'Temuan draft dibuat.');

        return $finding;
    }

    public function update(Finding $finding, array $data): Finding
    {
        (new ClosedFindingGuard())->assertEditable($finding);

        $old = $this->snapshot($finding);

        if ($finding->status === FindingStatus::Draft) {
            if (isset($data['response_period_start'])) {
                $data['fiscal_year'] = $this->calculateFiscalYear($data['response_period_start']);
            }
            $allowed = $data;
        } else {
            $allowed = array_intersect_key($data, array_flip(self::POST_REGISTER_FIELDS));
            if (empty($allowed)) {
                throw ValidationException::withMessages([
                    'status' => 'Setelah didaftarkan hanya judul, ruang lingkup, dan nama sumber yang dapat diubah.',
                ]);
            }
        }

        $finding->update($allowed);

        $this->audit('finding.updated', $finding, 'Temuan diperbarui.', [
            'old' => $old,
            'new' => $this->snapshot($finding),
        ]);

        return $finding->refresh();
    }

    public function register(Finding $finding, array $department_ids): Finding
    {
        (new ClosedFindingGuard())->assertEditable($finding);

        if ($finding->status !== FindingStatus::Draft) {
            throw ValidationException::withMessages([
                'status' => 'Hanya temuan Draft yang dapat didaftarkan.',
            ]);
        }

        $missing = array_filter(self::REGISTER_REQUIRED, fn ($field) => empty($finding->{$field}));
        if (!empty($missing)) {
            throw ValidationException::withMessages([
                'fields' => 'Data registrasi belum lengkap: ' . implode(', ', $missing) . '.',
            ]);
        }

        if ($finding->source === FindingSource::Lainnya->value && empty($finding->source_name)) {
            throw ValidationException::withMessages([
                'source_name' => 'Nama sumber wajib diisi untuk sumber Lainnya.',
            ]);
        }

        if (empty($department_ids)) {
            throw ValidationException::withMessages([
                'departments' => 'Minimal satu departemen auditee harus dipilih.',
            ]);
        }

        $auditee_ids = Department::where('is_auditee', true)->whereIn('id', $department_ids)->pluck('id')->all();
        if (count($auditee_ids) !== count(array_unique($department_ids))) {
            throw ValidationException::withMessages([
                'departments' => 'Semua departemen harus berstatus auditee.',
            ]);
        }

        if ($finding->documents()->count() === 0) {
            throw ValidationException::withMessages([
                'documents' => 'Minimal satu dokumen LHP harus dilampirkan.',
            ]);
        }

        $finding->update([
            'registration_number' => FindingNumberService::generate($finding->fiscal_year),
            'status' => FindingStatus::Terdaftar,
        ]);
        $finding->auditee_departments()->sync($auditee_ids);

        $this->audit('finding.registered', $finding, "Temuan {$finding->registration_number} didaftarkan.");
        FindingRegistered::dispatch($finding);

        return $finding->refresh();
    }

    public function activate(Finding $finding): Finding
    {
        (new ClosedFindingGuard())->assertEditable($finding);

        if ($finding->status !== FindingStatus::Terdaftar) {
            throw ValidationException::withMessages([
                'status' => 'Hanya temuan Terdaftar yang dapat diaktifkan.',
            ]);
        }

        $finding->update([
            'status' => FindingStatus::ProsessTindakLanjut,
            'activated_at' => now(),
        ]);

        $this->audit('finding.activated', $finding, 'Temuan diaktifkan.');
        FindingActivated::dispatch($finding);

        return $finding->refresh();
    }

    private function snapshot(Finding $finding): array
    {
        $fields = collect(self::POST_REGISTER_FIELDS)
            ->union(['lhp_number', 'lhp_date', 'finding_date', 'response_period_start', 'response_period_end', 'fiscal_year']);

        return $fields->mapWithKeys(function ($field) use ($finding) {
            $value = $finding->getAttribute($field);

            return [$field => $value instanceof \Carbon\CarbonInterface ? $value->format('Y-m-d') : $value];
        })->all();
    }

    private function audit(string $action, Finding $finding, string $description, ?array $data = null): void
    {
        AuditLogger::log($action, auth()->id(), request()?->ip(), $description, array_merge([
            'finding_id' => $finding->id,
        ], $data ?? []));
    }

    private function calculateFiscalYear(?string $date): ?int
    {
        return $date ? (int) \Carbon\Carbon::parse($date)->year : null;
    }
}
