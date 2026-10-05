<?php

use App\Enums\EvidenceStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evidence_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('action_plan_id')->constrained()->cascadeOnDelete();
            $table->enum('status', array_column(EvidenceStatus::cases(), 'value'))
                ->default(EvidenceStatus::Diajukan->value);
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('revision_note')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['action_plan_id', 'status']);
        });

        Schema::create('evidence_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evidence_submission_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('path');
            $table->string('mime');
            $table->unsignedBigInteger('size')->default(0);
            $table->string('label')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('evidence_submission_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evidence_files');
        Schema::dropIfExists('evidence_submissions');
    }
};