<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finding_sources', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::table('findings', function (Blueprint $table) {
            $table->string('source')->nullable()->change();
            $table->dropColumn('source_name');
        });
    }

    public function down(): void
    {
        Schema::table('findings', function (Blueprint $table) {
            $table->enum('source', ['BPK', 'BPKP', 'KAP', 'LAINNYA'])->nullable()->change();
            $table->string('source_name')->nullable()->after('source');
        });

        Schema::dropIfExists('finding_sources');
    }
};
