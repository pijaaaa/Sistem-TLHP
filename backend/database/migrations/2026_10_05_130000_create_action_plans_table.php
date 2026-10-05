<?php

use App\Enums\ActionPlanStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('action_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finding_department_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('weight', 5, 2)->default(0);
            $table->enum('status', array_column(ActionPlanStatus::cases(), 'value'))
                ->default(ActionPlanStatus::Draft->value);
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->date('due_date')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['finding_department_id', 'status']);
        });

        Schema::create('action_plan_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('action_plan_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('path');
            $table->string('mime');
            $table->unsignedBigInteger('size')->default(0);
            $table->string('label')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('action_plan_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('action_plan_documents');
        Schema::dropIfExists('action_plans');
    }
};
