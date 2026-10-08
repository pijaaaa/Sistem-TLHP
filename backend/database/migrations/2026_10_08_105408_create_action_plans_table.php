<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('action_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finding_id')->constrained('findings')->cascadeOnDelete();
            $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
            $table->string('code')->unique();
            $table->string('title')->nullable();
            $table->text('condition')->nullable();
            $table->text('criteria')->nullable();
            $table->text('cause')->nullable();
            $table->text('impact')->nullable();
            $table->enum('risk', ['RENDAH', 'SEDANG', 'TINGGI', 'KRITIS'])->nullable();
            $table->date('deadline')->nullable();
            $table->decimal('loss_idr', 15, 0)->nullable();
            $table->decimal('loss_usd', 15, 2)->nullable();
            $table->enum('status', ['DRAFT', 'MENUNGGU_PENENTUAN_PIC', 'PROSES_TINDAK_LANJUT', 'DIAJUKAN_KE_SPI', 'REVISI_SPI', 'SESUAI', 'CLOSED'])->default('DRAFT');
            $table->integer('current_revision')->default(0);
            $table->decimal('progress', 5, 2)->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index('finding_id');
            $table->index('department_id');
            $table->index('status');
            $table->unique(['finding_id', 'department_id', 'current_revision']);
        });

        Schema::create('action_plan_assignees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('action_plan_id')->constrained('action_plans')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['action_plan_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('action_plan_assignees');
        Schema::dropIfExists('action_plans');
    }
};
