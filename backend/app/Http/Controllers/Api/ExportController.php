<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActionPlan;
use App\Models\Audit;
use App\Models\Finding;
use App\Services\PermissionService;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Export CSV (dibuka langsung di Excel). Sengaja tanpa dependency Excel
 * agar tidak menambah library untuk kebutuhan yang sudah terpenuhi CSV.
 */
class ExportController extends Controller
{
    public function findings(Request $request): StreamedResponse
    {
        abort_unless(PermissionService::can(auth()->user(), 'exports', 'view'), 403);

        $rows = Finding::query()
            ->visible()
            ->withCount('documents')
            ->orderBy('id', 'desc')
            ->get()
            ->map(fn (Finding $f) => [
                $f->code,
                $f->title,
                $f->finding_date?->toDateString(),
                $f->severity,
                $f->status->label(),
                'Ronde ' . $f->current_round,
                $f->assessment_status?->label(),
                number_format(\App\Services\ActionPlanService::findingProgress($f->id), 2) . '%',
                $f->documents_count,
                $f->created_at?->toDateString(),
            ]);

        return $this->csv(
            'temuan-' . now()->format('Ymd-His') . '.csv',
            ['Kode', 'Judul', 'Tanggal', 'Tingkat', 'Status', 'Ronde', 'Assessment', 'Progress', 'Dokumen', 'Dibuat'],
            $rows,
        );
    }

    public function actionPlans(Request $request): StreamedResponse
    {
        abort_unless(PermissionService::can(auth()->user(), 'exports', 'view'), 403);

        $user = auth()->user();

        $query = ActionPlan::query()->with('findingDepartment.finding', 'creator');

        // Scope visibilitas mengikuti peran yang sama dengan halaman Rencana Aksi.
        if ($user->role === \App\Enums\Role::ManagerDept) {
            $query->whereIn('finding_department_id', function ($q) use ($user) {
                $q->select('id')->from('finding_departments')->where('department_id', $user->department_id);
            });
        } elseif ($user->role === \App\Enums\Role::StaffDept) {
            $query->whereIn('finding_department_id', function ($q) use ($user) {
                $q->select('finding_department_id')->from('finding_department_pics')
                    ->where('user_id', $user->id);
            });
        }

        $rows = $query
            ->orderBy('id', 'desc')
            ->limit(5000)
            ->get()
            ->map(fn (ActionPlan $ap) => [
                $ap->findingDepartment?->finding?->code,
                $ap->title,
                $ap->creator?->name,
                $ap->weight,
                $ap->status->label(),
                'Ronde ' . $ap->round,
                $ap->approved_at?->toDateString(),
            ]);

        return $this->csv(
            'rencana-aksi-' . now()->format('Ymd-His') . '.csv',
            ['Kode Temuan', 'Judul', 'PIC', 'Bobot', 'Status', 'Ronde', 'Disetujui'],
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
                $a->created_at?->toDateTimeString(),
                $a->action,
                $a->entity_type,
                $a->entity_id,
                $a->user?->name,
                $a->ip_address,
                $a->description,
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