<?php

use App\Enums\FindingStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('findings')) {
            return;
        }

        Schema::table('findings', function (Blueprint $table) {
            $table->enum('status', array_column(FindingStatus::cases(), 'value'))
                ->default(FindingStatus::Draft->value)
                ->change();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('findings')) {
            return;
        }

        Schema::table('findings', function (Blueprint $table) {
            $table->enum('status', array_map('strtolower', array_column(FindingStatus::cases(), 'value')))
                ->default(strtolower(FindingStatus::Draft->value))
                ->change();
        });
    }
};
