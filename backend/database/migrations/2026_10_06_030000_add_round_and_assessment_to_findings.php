<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addVerificationStatusToEnum();

        Schema::table('findings', function (Blueprint $table) {
            $table->unsignedSmallInteger('current_round')->default(1);
            $table->string('assessment_status')->nullable();
            $table->text('assessment_note')->nullable();
            $table->foreignId('assessed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assessed_at')->nullable();
        });

        Schema::table('finding_departments', function (Blueprint $table) {
            $table->unsignedSmallInteger('round')->default(1);
        });

        Schema::table('action_plans', function (Blueprint $table) {
            $table->unsignedSmallInteger('round')->default(1);
        });

        Schema::table('evidence_submissions', function (Blueprint $table) {
            $table->unsignedSmallInteger('round')->default(1);
        });

        Schema::table('finding_department_pics', function (Blueprint $table) {
            $table->unsignedSmallInteger('round')->default(1);
        });
    }

    public function down(): void
    {
        Schema::table('finding_department_pics', function (Blueprint $table) {
            $table->dropColumn('round');
        });

        Schema::table('evidence_submissions', function (Blueprint $table) {
            $table->dropColumn('round');
        });

        Schema::table('action_plans', function (Blueprint $table) {
            $table->dropColumn('round');
        });

        Schema::table('finding_departments', function (Blueprint $table) {
            $table->dropColumn('round');
        });

        Schema::table('findings', function (Blueprint $table) {
            $table->dropColumn(['current_round', 'assessment_status', 'assessment_note', 'assessed_by', 'assessed_at']);
        });
    }

    /**
     * Menambahkan nilai 'menunggu_verifikasi_spi' pada kolom enum findings.status.
     * SQLite menyimpan enum sebagai VARCHAR sehingga tidak perlu perubahan skema.
     */
    protected function addVerificationStatusToEnum(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE findings MODIFY status ENUM(
            'draft','dikirim_ke_ia','didistribusikan','dalam_proses',
            'menunggu_assessment_ia','menunggu_verifikasi_spi','closed','case_closed'
        )");
    }
};