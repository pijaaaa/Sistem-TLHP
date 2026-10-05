<?php

use App\Enums\FindingStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('findings', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('title');
            $table->date('finding_date')->nullable();
            $table->string('severity')->default('medium');
            $table->text('recommendation')->nullable();
            $table->text('auditor_action_plan')->nullable();
            $table->enum('status', array_column(FindingStatus::cases(), 'value'))->default(FindingStatus::Draft->value);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('findings');
    }
};
