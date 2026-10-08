<?php

use App\Enums\InboxTaskStatus;
use App\Enums\InboxTaskType;
use App\Enums\ReminderType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inbox_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipient_id')->constrained('users')->cascadeOnDelete();
            $table->enum('task_type', array_column(InboxTaskType::cases(), 'value'));
            $table->morphs('subject');
            $table->string('title');
            $table->timestamp('received_at');
            $table->timestamp('first_opened_at')->nullable();
            $table->timestamp('acted_at')->nullable();
            $table->enum('status', array_column(InboxTaskStatus::cases(), 'value'))->default(InboxTaskStatus::Open->value);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['recipient_id', 'status']);
        });

        Schema::create('reminder_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->morphs('subject');
            $table->enum('reminder_type', array_column(ReminderType::cases(), 'value'));
            $table->date('sent_on');
            $table->timestamps();

            $table->unique(['user_id', 'subject_type', 'subject_id', 'reminder_type', 'sent_on'], 'reminder_logs_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reminder_logs');
        Schema::dropIfExists('inbox_tasks');
    }
};