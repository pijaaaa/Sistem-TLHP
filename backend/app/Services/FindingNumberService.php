<?php

namespace App\Services;

use App\Models\FindingSequence;
use Illuminate\Support\Facades\DB;

class FindingNumberService
{
    public static function generate(int $fiscal_year): string
    {
        return DB::transaction(function () use ($fiscal_year) {
            $sequence = FindingSequence::lockForUpdate()
                ->firstOrCreate(
                    ['fiscal_year' => $fiscal_year],
                    ['sequence' => 0]
                );

            $sequence->increment('sequence');

            return sprintf('TLHT-%d-%04d', $fiscal_year, $sequence->sequence);
        });
    }
}
