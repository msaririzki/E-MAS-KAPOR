<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Add NRP/NIP as unique login identifier
            $table->string('nrp_nip')->unique()->after('id');

            // Add satker reference
            $table->foreignId('satker_id')->nullable()->after('password')
                ->constrained('satkers')->nullOnDelete();

            // Add active flag
            $table->boolean('is_active')->default(true)->after('satker_id');

            // Make email nullable (NRP/NIP is primary identifier)
            $table->string('email')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['satker_id']);
            $table->dropColumn(['nrp_nip', 'satker_id', 'is_active']);
        });
    }
};
