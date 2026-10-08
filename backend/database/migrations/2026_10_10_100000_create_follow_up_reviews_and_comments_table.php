<?php

use App\Enums\CommentKind;
use App\Enums\ReviewDecision;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('follow_up_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('follow_up_id')->constrained('follow_ups')->cascadeOnDelete();
            $table->foreignId('reviewer_id')->constrained('users')->cascadeOnDelete();
            $table->enum('decision', array_column(ReviewDecision::cases(), 'value'));
            $table->string('note')->nullable();
            $table->unsignedInteger('weight_before');
            $table->unsignedInteger('weight_after')->nullable();
            $table->timestamps();

            $table->index(['follow_up_id', 'created_at']);
        });

        Schema::create('follow_up_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('follow_up_id')->constrained('follow_ups')->cascadeOnDelete();
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->enum('kind', array_column(CommentKind::cases(), 'value'));
            $table->text('body');
            $table->softDeletes();
            $table->timestamps();

            $table->index(['follow_up_id', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('follow_up_comments');
        Schema::dropIfExists('follow_up_reviews');
    }
};