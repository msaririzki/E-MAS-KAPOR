<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('personnels', function (Blueprint $table) {
            $table->boolean('has_nrp_issue')->default(false)->after('is_active');
            $table->text('nrp_issue_note')->nullable()->after('has_nrp_issue');
            $table->timestamp('nrp_issue_resolved_at')->nullable()->after('nrp_issue_note');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personnels', function (Blueprint $table) {
            $table->dropColumn(['has_nrp_issue', 'nrp_issue_note', 'nrp_issue_resolved_at']);
        });
    }
};
