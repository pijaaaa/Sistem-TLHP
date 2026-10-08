<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('action_plans', function (Blueprint $table) {
            $table->index(['finding_id', 'department_id']);
        });

        Schema::table('follow_ups', function (Blueprint $table) {
            $table->index(['target_date', 'status']);
        });

        Schema::table('inbox_tasks', function (Blueprint $table) {
            $table->index(['received_at', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('action_plans', function (Blueprint $table) {
            $table->dropIndex(['finding_id', 'department_id']);
        });

        Schema::table('follow_ups', function (Blueprint $table) {
            $table->dropIndex(['target_date', 'status']);
        });

        Schema::table('inbox_tasks', function (Blueprint $table) {
            $table->dropIndex(['received_at', 'status']);
        });
    }
};