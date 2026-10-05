<?php

namespace App\Services;

use App\Enums\FindingStatus;
use App\Models\Finding;
use App\Models\FindingDocument;
use App\Support\AuditLogger;
use App\Support\CacheService;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class FindingService
{
    // ponytail: list temuan tidak di-cache karena Paginator bersifat stateful per-request
    // dan serializer cache merepotkan; cache hanya untuk master data (departemen/employees/permissions).
    public static function paginated(int $perPage = 15): LengthAwarePaginator
    {
        return Finding::visible()->withCount('documents')->orderBy('id', 'desc')->paginate($perPage);
    }

    public static function invalidate(): void
    {
        CacheService::incrementGroupVersion('dashboard');
    }

    public static function create(array $data): Finding
    {
        $data['created_by'] = $data['created_by'] ?? auth()->id();
        $finding = Finding::create($data);
        self::invalidate();

        AuditLogger::log('finding.created', auth()->id(), request()->ip(), 'Temuan dibuat.', [
            'finding_id' => $finding->id,
            'code' => $finding->code,
        ]);

        return $finding->fresh();
    }

    public static function update(Finding $finding, array $data): Finding
    {
        $finding->fill($data);
        $finding->save();
        self::invalidate();

        AuditLogger::log('finding.updated', auth()->id(), request()->ip(), 'Temuan diperbarui.', [
            'finding_id' => $finding->id,
            'fields' => array_keys($data),
        ]);

        return $finding->fresh();
    }

    public static function delete(Finding $finding): void
    {
        $finding->delete();
        self::invalidate();

        AuditLogger::log('finding.deleted', auth()->id(), request()->ip(), 'Temuan dihapus.', [
            'finding_id' => $finding->id,
            'code' => $finding->code,
        ]);
    }

    public static function sendToIA(Finding $finding): Finding
    {
        if ($finding->status !== FindingStatus::Draft) {
            throw ValidationException::withMessages([
                'status' => 'Hanya temuan dalam status Draft yang dapat dikirim ke IA.',
            ]);
        }

        $finding->status = FindingStatus::SentToIa;
        $finding->save();
        self::invalidate();

        AuditLogger::log('finding.sent_to_ia', auth()->id(), request()->ip(), 'Temuan dikirim ke IA.', [
            'finding_id' => $finding->id,
        ]);

        return $finding->fresh();
    }

    public static function uploadDocument(Finding $finding, UploadedFile $file, ?string $label): FindingDocument
    {
        $path = $file->store('findings/' . $finding->id, config('upload.disk'));

        return $finding->documents()->create([
            'name' => $file->getClientOriginalName(),
            'path' => $path,
            'mime' => $file->getMimeType() ?? 'application/octet-stream',
            'size' => $file->getSize(),
            'label' => $label,
            'uploaded_by' => auth()->id(),
        ]);
    }

    public static function deleteDocument(FindingDocument $document): void
    {
        Storage::disk(config('upload.disk'))->delete($document->path);
        $document->delete();
        self::invalidate();
    }
}
