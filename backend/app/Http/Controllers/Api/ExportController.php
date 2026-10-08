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

    public function reportDepartments(Request $request): StreamedResponse
    {
        abort_unless(PermissionService::can(auth()->user(), 'exports', 'view'), 403);

        $aps = ActionPlan::query()->with('department')->get(['id', 'department_id', 'progress', 'risk']);
        $apIds = $aps->pluck('id');
        $fups = $apIds->isNotEmpty()
            ? \App\Models\FollowUp::query()->whereIn('action_plan_id', $apIds)->get(['action_plan_id', 'status', 'target_date'])
            : collect();

        $rows = $aps->groupBy('department_id')->map(function ($group) use ($fups) {
            $ids = $group->pluck('id');
            $items = $fups->whereIn('action_plan_id', $ids);
            return [
                $group[0]->department?->name ?? '-',
                $ids->count(),
                $items->count(),
                round($group->avg('progress') ?? 0, 2),
                $group->count() ? $items->where('status', 'SELESAI')->count() : 0,
                $items->reject(fn ($f) => $f->status === 'SELESAI')
                    ->filter(fn ($f) => $f->target_date && $f->target_date < now())->count(),
            ];
        })->values();

        return $this->csv(
            'rekap-departemen-' . now()->format('Ymd-His') . '.csv',
            ['Departemen', 'Action Plan', 'Tindak Lanjut', 'Progres Rata-rata', 'Selesai', 'Terlambat'],
            $rows,
        );
    }

    public function reportLate(Request $request): StreamedResponse
    {
        abort_unless(PermissionService::can(auth()->user(), 'exports', 'view'), 403);

        $rows = \App\Models\FollowUp::query()
            ->where('status', '!=', 'SELESAI')
            ->where('target_date', '<', now())
            ->with(['actionPlan.finding', 'actionPlan.department'])
            ->get()
            ->map(fn ($f) => [
                $f->actionPlan?->code ?? '-',
                $f->description,
                $f->target_date?->toDateString() ?? '-',
                (int) $f->target_date->diffInDays(now()),
                $f->actionPlan?->department?->name ?? '-',
                $f->actionPlan?->finding?->registration_number ?? '-',
            ]);

        return $this->csv(
            'keterlambatan-' . now()->format('Ymd-His') . '.csv',
            ['Action Plan', 'Tindak Lanjut', 'Target', 'Hari Terlambat', 'Departemen', 'Temuan'],
            $rows,
        );
    }

    public function reportRisk(Request $request): StreamedResponse
    {
        abort_unless(PermissionService::can(auth()->user(), 'exports', 'view'), 403);

        $rows = ActionPlan::query()->get(['risk', 'loss_idr', 'loss_usd'])
            ->map(fn ($ap) => ['risk' => $ap->risk?->value ?? '-', 'idr' => (float) ($ap->loss_idr ?? 0), 'usd' => (float) ($ap->loss_usd ?? 0)])
            ->groupBy('risk')
            ->map(fn ($g) => [
                $g[0]['risk'],
                $g->count(),
                number_format($g->sum('idr'), 0, ',', '.'),
                number_format($g->sum('usd'), 2, ',', '.'),
            ])
            ->values();

        return $this->csv(
            'risiko-' . now()->format('Ymd-His') . '.csv',
            ['Risiko', 'Jumlah AP', 'Potensi Kerugian (IDR)', 'Potensi Kerugian (USD)'],
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
