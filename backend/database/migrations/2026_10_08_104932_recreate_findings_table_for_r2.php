<?php

use App\Enums\FindingStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('finding_documents');
        Schema::dropIfExists('findings');

        Schema::create('findings', function (Blueprint $table) {
            $table->id();
            $table->string('registration_number')->nullable()->unique();
            $table->enum('source', ['BPK', 'BPKP', 'KAP', 'LAINNYA'])->nullable();
            $table->string('source_name')->nullable();
            $table->string('lhp_number')->nullable();
            $table->date('lhp_date')->nullable();
            $table->date('finding_date')->nullable();
            $table->date('response_period_start')->nullable();
            $table->date('response_period_end')->nullable();
            $table->integer('fiscal_year')->nullable();
            $table->text('scope')->nullable();
            $table->string('title')->nullable();
            $table->enum('status', array_column(FindingStatus::cases(), 'value'))->default(FindingStatus::Draft->value);
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index('status');
            $table->index('fiscal_year');
            $table->index('response_period_start');
        });

        Schema::create('finding_auditee_departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finding_id')->constrained('findings')->cascadeOnDelete();
            $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['finding_id', 'department_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finding_auditee_departments');
        Schema::dropIfExists('findings');
    }
};
