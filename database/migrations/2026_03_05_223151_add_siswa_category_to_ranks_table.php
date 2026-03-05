<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Tambah kategori 'SISWA' ke enum 'category' di tabel ranks
        DB::statement("ALTER TABLE ranks MODIFY COLUMN category ENUM('PATI','PAMEN','PAMA','BINTARA','PNS','SISWA')");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE ranks MODIFY COLUMN category ENUM('PATI','PAMEN','PAMA','BINTARA','PNS')");
    }
};
