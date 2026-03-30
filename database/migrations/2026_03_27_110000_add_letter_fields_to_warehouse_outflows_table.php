<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouse_outflows', function (Blueprint $table) {
            $table->string('letter_number')->nullable()->after('reference_note');
            $table->date('letter_date')->nullable()->after('letter_number');
        });
    }

    public function down(): void
    {
        Schema::table('warehouse_outflows', function (Blueprint $table) {
            $table->dropColumn(['letter_number', 'letter_date']);
        });
    }
};
