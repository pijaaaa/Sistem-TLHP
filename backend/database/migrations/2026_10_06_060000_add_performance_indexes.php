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
        // Dashboard + daftar temuan: filter status lalu urut id.
        Schema::table('findings', function (Blueprint $table) {
            $table->index(['status', 'id'], 'findings_status_id_index');
            $table->index('current_round');
        });

        // Halaman Finding Departments: filter departemen PIC lalu urut id.
        Schema::table('finding_departments', function (Blueprint $table) {
            $table->index(['department_id', 'status'], 'fd_department_status_index');
            $table->index(['finding_id', 'round'], 'fd_finding_round_index');
        });

        // Halaman Rencana Aksi: filter PIC/departemen lalu urut id.
        Schema::table('action_plans', function (Blueprint $table) {
            $table->index(['created_by', 'status'], 'ap_creator_status_index');
            $table->index('status');
        });

        // Filter audit trail per aksi lalu urut id.
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

        Schema::table('action_plans', function (Blueprint $table) {
            $table->dropIndex('ap_creator_status_index');
            $table->dropIndex('status');
        });

        Schema::table('finding_departments', function (Blueprint $table) {
            $table->dropIndex('fd_department_status_index');
            $table->dropIndex('fd_finding_round_index');
        });

        Schema::table('findings', function (Blueprint $table) {
            $table->dropIndex('findings_status_id_index');
            $table->dropIndex('current_round');
        });
    }
};