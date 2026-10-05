<?php

use App\Enums\FindingDepartmentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finding_departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finding_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', array_column(FindingDepartmentStatus::cases(), 'value'))
                ->default(FindingDepartmentStatus::Received->value);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('finding_department_pics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finding_department_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['finding_department_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finding_department_pics');
        Schema::dropIfExists('finding_departments');
    }
};
