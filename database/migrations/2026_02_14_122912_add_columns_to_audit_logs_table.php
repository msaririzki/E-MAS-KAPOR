<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->string('category')->after('action')->nullable();
            $table->string('status')->after('category')->default('success'); // success, failed
            $table->string('details')->after('status')->nullable();
            $table->text('user_agent')->after('ip_address')->nullable();

            // Allow auditable_id/type to be null (for login/logout events)
            $table->string('auditable_type')->nullable()->change();
            $table->unsignedBigInteger('auditable_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropColumn(['category', 'status', 'details', 'user_agent']);
            $table->string('auditable_type')->change();
            $table->unsignedBigInteger('auditable_id')->change();
        });
    }
};
