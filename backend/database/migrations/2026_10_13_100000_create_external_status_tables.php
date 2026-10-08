<?php

use App\Enums\ExternalStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_status_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finding_id')->constrained('findings')->cascadeOnDelete();
            $table->enum('status', array_column(ExternalStatus::cases(), 'value'));
            $table->string('note')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('recorded_at');
            $table->softDeletes();
            $table->timestamps();

            $table->index(['finding_id', 'recorded_at']);
        });

        Schema::create('external_status_action_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('record_id')->constrained('external_status_records')->cascadeOnDelete();
            $table->foreignId('action_plan_id')->constrained('action_plans')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['record_id', 'action_plan_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_status_action_plans');
        Schema::dropIfExists('external_status_records');
    }
};