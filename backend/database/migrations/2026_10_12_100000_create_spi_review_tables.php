<?php

use App\Enums\RevisionSource;
use App\Enums\SpiResult;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spi_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('action_plan_id')->constrained('action_plans')->cascadeOnDelete();
            $table->unsignedInteger('revision_no');
            $table->foreignId('reviewer_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['action_plan_id', 'revision_no']);
        });

        Schema::create('spi_review_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spi_review_id')->constrained('spi_reviews')->cascadeOnDelete();
            $table->foreignId('follow_up_id')->constrained('follow_ups')->cascadeOnDelete();
            $table->enum('result', array_column(SpiResult::cases(), 'value'));
            $table->string('note')->nullable();
            $table->timestamps();

            $table->unique(['spi_review_id', 'follow_up_id']);
        });

        Schema::create('action_plan_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('action_plan_id')->constrained('action_plans')->cascadeOnDelete();
            $table->unsignedInteger('revision_no');
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('requested_role')->nullable();
            $table->enum('source', array_column(RevisionSource::cases(), 'value'));
            $table->text('reason');
            $table->timestamp('requested_at');
            $table->timestamp('forwarded_to_pic_at')->nullable();
            $table->date('new_deadline')->nullable();
            $table->timestamps();

            $table->index(['action_plan_id', 'revision_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('action_plan_revisions');
        Schema::dropIfExists('spi_review_items');
        Schema::dropIfExists('spi_reviews');
    }
};