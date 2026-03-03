<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kapor_items', function (Blueprint $table) {
            $table->decimal('price', 15, 2)->nullable()->after('description')
                ->comment('Harga satuan dalam Rupiah');
            $table->string('unit', 50)->default('PCS')->after('price')
                ->comment('Satuan: PCS, STEL, PASANG, dll');
            $table->string('image')->nullable()->after('unit')
                ->comment('Path gambar item');
            $table->string('invoice_group')->nullable()->after('image')
                ->comment('Nama group untuk pengelompokan di invoice/HPS, misal: PDL PNS, PDL POLRI');
            $table->json('default_recipients')->nullable()->after('unit_keywords')
                ->comment('Filter default penerima: {"personnel_type":["polri"],"gender":["L"],"rank_categories":["PAMEN"]}');
        });
    }

    public function down(): void
    {
        Schema::table('kapor_items', function (Blueprint $table) {
            $table->dropColumn(['price', 'unit', 'image', 'invoice_group', 'default_recipients']);
        });
    }
};
