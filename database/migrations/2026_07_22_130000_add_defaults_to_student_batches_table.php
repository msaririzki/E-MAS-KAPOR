<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_batches', function (Blueprint $table) {
            $table->foreignId('default_rank_id')->nullable()->after('procurement_group')->constrained('ranks')->nullOnDelete();
            $table->string('default_jabatan')->nullable()->after('default_rank_id');
            $table->string('default_bagian')->nullable()->after('default_jabatan');
        });
    }

    public function down(): void
    {
        Schema::table('student_batches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('default_rank_id');
            $table->dropColumn(['default_jabatan', 'default_bagian']);
        });
    }
};
