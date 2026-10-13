<?php

namespace App\Services;

use App\Models\FindingSource;
use App\Support\CacheService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class FindingSourceService
{
    public static function active(): Collection
    {
        return CacheService::remember('lookups', 'finding_sources', fn () => FindingSource::where('is_active', true)->orderBy('name')->get(), 3600);
    }

    /** @return array<string, string> code => name */
    public static function labels(): array
    {
        return CacheService::remember('lookups', 'finding_sources_labels', fn () => FindingSource::orderBy('name')->pluck('name', 'code')->all(), 3600);
    }

    public static function paginated(int $perPage = 15): LengthAwarePaginator
    {
        return FindingSource::orderBy('name')->paginate($perPage);
    }

    public static function create(array $data): FindingSource
    {
        $source = FindingSource::create($data);
        self::invalidate();
        return $source;
    }

    public static function update(FindingSource $source, array $data): FindingSource
    {
        $source->update($data);
        self::invalidate();
        return $source->fresh();
    }

    public static function delete(FindingSource $source): void
    {
        $source->delete();
        self::invalidate();
    }

    public static function invalidate(): void
    {
        CacheService::flushGroup('lookups');
    }
}
