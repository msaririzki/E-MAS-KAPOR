<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personnels', function (Blueprint $table) {
            $table->foreignId('student_batch_id')
                ->nullable()
                ->after('user_id')
                ->constrained('student_batches')
                ->nullOnDelete();
            $table->string('student_code')->nullable()->unique()->after('student_batch_id');
            $table->string('procurement_group', 30)->nullable()->after('personnel_type');

            $table->index(['student_batch_id', 'is_active']);
            $table->index('procurement_group');
        });
    }

    public function down(): void
    {
        Schema::table('personnels', function (Blueprint $table) {
            $table->dropForeign(['student_batch_id']);
            $table->dropUnique(['student_code']);
            $table->dropIndex(['student_batch_id', 'is_active']);
            $table->dropIndex(['procurement_group']);
            $table->dropColumn(['student_batch_id', 'student_code', 'procurement_group']);
        });
    }
};
