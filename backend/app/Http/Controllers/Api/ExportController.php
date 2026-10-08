<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActionPlan;
use App\Models\Audit;
use App\Models\Finding;
use App\Services\PermissionService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function findings(Request $request): StreamedResponse
    {
        abort_unless(PermissionService::can(auth()->user(), 'exports', 'view'), 403);

        $findings = Finding::query()
            ->with('documents')
            ->orderBy('id', 'desc')
            ->limit(5000)
            ->get();

        $rows = $findings->map(fn (Finding $f) => [
            $f->registration_number ?? '-',
            $f->source ?? '-',
            $f->source_name ?? '-',
            $f->lhp_number ?? '-',
            $f->lhp_date?->toDateString() ?? '-',
            $f->finding_date?->toDateString() ?? '-',
            (string) $f->fiscal_year,
            $f->title ?? '-',
            $f->status->label(),
            (string) $f->age_days,
            (int) $f->documents->count(),
            $f->created_at?->toDateString() ?? '-',
        ]);

        return $this->csv(
            'temuan-' . now()->format('Ymd-His') . '.csv',
            ['No Registrasi', 'Sumber', 'Nama Sumber', 'No LHP', 'Tgl LHP', 'Tgl Temuan', 'Tahun Buku', 'Judul', 'Status', 'Umur (hari)', 'Dokumen', 'Dibuat'],
            $rows,
        );
    }

    public function actionPlans(Request $request): StreamedResponse
    {
        abort_unless(PermissionService::can(auth()->user(), 'exports', 'view'), 403);

        $rows = ActionPlan::query()
            ->with('finding', 'department', 'assignees')
            ->orderBy('id', 'desc')
            ->limit(5000)
            ->get()
            ->map(fn (ActionPlan $ap) => [
                $ap->code,
                $ap->finding?->registration_number ?? '-',
                $ap->department?->name ?? '-',
                $ap->title,
                $ap->risk?->label() ?? '-',
                $ap->deadline?->toDateString() ?? '-',
                (string) $ap->progress,
                $ap->status->label(),
                implode('; ', $ap->assignees->pluck('name')->all()),
            ]);

        return $this->csv(
            'rencana-aksi-' . now()->format('Ymd-His') . '.csv',
            ['Kode', 'No Registrasi', 'Departemen', 'Judul', 'Risiko', 'Deadline', 'Progress', 'Status', 'PIC'],
            $rows,
        );
    }

    public function auditTrail(Request $request): StreamedResponse
    {
        abort_unless(PermissionService::can(auth()->user(), 'audit_trail', 'view'), 403);

        $rows = Audit::query()
            ->with('user')
            ->orderBy('id', 'desc')
            ->limit(5000)
            ->get()
            ->map(fn (Audit $a) => [
                $a->created_at?->toDateTimeString() ?? '-',
                $a->action,
                $a->entity_type ?? '-',
                (string) ($a->entity_id ?? '-'),
                $a->user?->name ?? '-',
                $a->ip_address ?? '-',
                $a->description ?? '-',
            ]);

        return $this->csv(
            'audit-trail-' . now()->format('Ymd-His') . '.csv',
            ['Waktu', 'Aksi', 'Entitas', 'ID', 'Pengguna', 'IP', 'Deskripsi'],
            $rows,
        );
    }

    private function csv(string $filename, array $headers, $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $headers);

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
