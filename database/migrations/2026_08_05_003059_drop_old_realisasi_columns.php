<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eku_transactions', function (Blueprint $table) {
            foreach ([
                'file_realisasi_setoran',
                'file_realisasi_penarikan',
                'file_realisasi_setoran_original',
                'file_realisasi_penarikan_original',
                'total_realisasi_setoran',
                'total_realisasi_penarikan',
                'deviasi_setoran',
                'deviasi_penarikan',
                'realisasi_uploaded_at',
            ] as $column) {
                if (Schema::hasColumn('eku_transactions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('eku_transaction_details', function (Blueprint $table) {
            foreach ([
                'realisasi_setoran',
                'realisasi_penarikan',
                'deviasi_setoran',
                'deviasi_penarikan',
            ] as $column) {
                if (Schema::hasColumn('eku_transaction_details', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    public function down(): void
    {
    }
};
