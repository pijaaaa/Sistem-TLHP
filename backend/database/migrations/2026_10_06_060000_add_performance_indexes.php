<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Index untuk query yang dijalankan pada setiap Opening halaman.
 * Composite mengikuti urutan filter -> sort yang dipakai service.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('findings', function (Blueprint $table) {
            $table->index(['status', 'id'], 'findings_status_id_index');
        });

        Schema::table('audits', function (Blueprint $table) {
            $table->index(['action', 'id'], 'audits_action_id_index');
            $table->index(['user_id', 'id'], 'audits_user_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('audits', function (Blueprint $table) {
            $table->dropIndex('audits_action_id_index');
            $table->dropIndex('audits_user_id_index');
        });

        Schema::table('findings', function (Blueprint $table) {
            $table->dropIndex('findings_status_id_index');
        });
    }
};