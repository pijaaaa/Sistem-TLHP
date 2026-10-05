<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finding_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finding_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('round');
            $table->text('auditor_result')->nullable();
            $table->string('auditor_conclusion')->nullable();
            $table->date('verified_date')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_closed')->default(false);
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['finding_id', 'round']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finding_verifications');
    }
};