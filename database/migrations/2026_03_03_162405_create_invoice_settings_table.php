<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_settings', function (Blueprint $table) {
            $table->id();
            $table->string('signatory_name')->default('');
            $table->string('signatory_rank')->default('');
            $table->string('signatory_nrp')->default('');
            $table->string('signatory_title')->default('PEJABAT PEMBUAT KOMITMEN');
            $table->string('location')->default('Mataram');
            $table->string('organization_name')->default('KEPALA BIRO LOGISTIK POLDA NTB');
            $table->string('header_title')->default('KEPOLISIAN NEGARA REPUBLIK INDONESIA DAERAH NUSA TENGGARA BARAT');
            $table->string('work_type')->default('Pengadaan KAPOR');
            $table->timestamps();
        });

        // Insert default row
        DB::table('invoice_settings')->insert([
            'signatory_name' => '',
            'signatory_rank' => '',
            'signatory_nrp' => '',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_settings');
    }
};
