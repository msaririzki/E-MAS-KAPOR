<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sdm_import_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('initiated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('preview_ready');
            $table->string('processing_mode')->default('sync');
            $table->json('source_files')->nullable();
            $table->json('summary')->nullable();
            $table->string('preview_payload_path')->nullable();
            $table->string('error_report_path')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sdm_import_runs');
    }
};
