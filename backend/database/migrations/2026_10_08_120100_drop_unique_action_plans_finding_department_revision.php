<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('action_plans', function (Blueprint $table) {
            $table->dropUnique(['finding_id', 'department_id', 'current_revision']);
        });
    }

    public function down(): void
    {
        Schema::table('action_plans', function (Blueprint $table) {
            $table->unique(['finding_id', 'department_id', 'current_revision']);
        });
    }
};
