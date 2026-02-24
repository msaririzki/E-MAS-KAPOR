<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    public function up(): void
    {
        Schema::table('personnels', function (Blueprint $table) {
            $table->string('nrp')->after('user_id')->nullable();
            $table->string('jabatan')->after('rank_id')->nullable();
            $table->string('bagian')->after('jabatan')->nullable();
            $table->string('avatar')->after('phone')->nullable();
            $table->text('address')->after('avatar')->nullable();

            // Allow user_id to be nullable if we want to manage personnel without user account
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('personnels', function (Blueprint $table) {
            $table->dropColumn(['nrp', 'jabatan', 'bagian', 'avatar', 'address']);
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });
    }
};
