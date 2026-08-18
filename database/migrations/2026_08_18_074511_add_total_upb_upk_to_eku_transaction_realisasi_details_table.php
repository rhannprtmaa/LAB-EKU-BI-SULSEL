<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eku_transaction_realisasi_details', function (Blueprint $table) {
            // Dipakai khusus oleh fitur "Upload Realisasi Harian Massal"
            // (RealisasiMassalSheetImport), yang cuma menangkap 2 angka
            // agregat per hari (Uang Pecahan Besar & Uang Pecahan Kecil),
            // BUKAN breakdown 11 pecahan lengkap seperti kolom
            // kertas_100k..logam_100 di atas (itu tetap dipakai jalur
            // input Realisasi satu-transaksi yang lama/manual).
            //
            // Nullable & default 0 supaya baris lama (dari jalur manual)
            // tetap valid tanpa perlu backfill.
            $table->decimal('total_upb', 20, 2)->nullable()->default(0)->after('subtotal');
            $table->decimal('total_upk', 20, 2)->nullable()->default(0)->after('total_upb');
        });
    }

    public function down(): void
    {
        Schema::table('eku_transaction_realisasi_details', function (Blueprint $table) {
            $table->dropColumn(['total_upb', 'total_upk']);
        });
    }
};
