<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eku_transactions', function (Blueprint $table) {
            // Sebelumnya cuma ada total_nominal (gabungan Setoran+Penarikan).
            // Sekarang dipisah juga per jenis, supaya tabel Daftar Pengajuan EKU
            // bisa tampilkan "Total Setoran" dan "Total Penarikan" masing-masing.
            $table->decimal('total_setoran', 20, 2)->default(0)->after('total_nominal');
            $table->decimal('total_penarikan', 20, 2)->default(0)->after('total_setoran');
        });
    }

    public function down(): void
    {
        Schema::table('eku_transactions', function (Blueprint $table) {
            $table->dropColumn(['total_setoran', 'total_penarikan']);
        });
    }
};
