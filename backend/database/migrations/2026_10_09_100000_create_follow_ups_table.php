<?php

use App\Enums\FollowUpStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('follow_ups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('action_plan_id')->constrained('action_plans')->cascadeOnDelete();
            $table->unsignedInteger('revision_no')->default(0);
            $table->text('description');
            $table->date('target_date');
            $table->unsignedInteger('weight');
            $table->unsignedTinyInteger('progress')->default(0);
            $table->enum('status', array_column(FollowUpStatus::cases(), 'value'))->default(FollowUpStatus::Draft->value);
            $table->foreignId('linked_follow_up_id')->nullable()->constrained('follow_ups')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['action_plan_id', 'revision_no']);
            $table->index('status');
        });

        Schema::create('follow_up_assignees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('follow_up_id')->constrained('follow_ups')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['follow_up_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('follow_up_assignees');
        Schema::dropIfExists('follow_ups');
    }
};