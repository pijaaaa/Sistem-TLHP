<?php

namespace Database\Seeders;

use App\Models\FindingSource;
use Illuminate\Database\Seeder;

class FindingSourceSeeder extends Seeder
{
    public function run(): void
    {
        $sources = [
            ['code' => 'BPK', 'name' => 'BPK'],
            ['code' => 'BPKP', 'name' => 'BPKP'],
            ['code' => 'KAP', 'name' => 'KAP'],
            ['code' => 'LAINNYA', 'name' => 'Lainnya'],
        ];

        foreach ($sources as $source) {
            FindingSource::updateOrCreate(
                ['code' => $source['code']],
                ['name' => $source['name'], 'is_active' => true],
            );
        }
    }
}
