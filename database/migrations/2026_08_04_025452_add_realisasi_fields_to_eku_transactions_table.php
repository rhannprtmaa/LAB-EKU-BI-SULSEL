<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eku_transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('eku_transactions', 'file_realisasi_setoran')) {
                $table->string('file_realisasi_setoran')->nullable();
            }
            if (! Schema::hasColumn('eku_transactions', 'file_realisasi_penarikan')) {
                $table->string('file_realisasi_penarikan')->nullable();
            }
            if (! Schema::hasColumn('eku_transactions', 'file_realisasi_setoran_original')) {
                $table->string('file_realisasi_setoran_original')->nullable();
            }
            if (! Schema::hasColumn('eku_transactions', 'file_realisasi_penarikan_original')) {
                $table->string('file_realisasi_penarikan_original')->nullable();
            }
            if (! Schema::hasColumn('eku_transactions', 'total_realisasi_setoran')) {
                $table->decimal('total_realisasi_setoran', 20, 2)->default(0);
            }
            if (! Schema::hasColumn('eku_transactions', 'total_realisasi_penarikan')) {
                $table->decimal('total_realisasi_penarikan', 20, 2)->default(0);
            }
            if (! Schema::hasColumn('eku_transactions', 'deviasi_setoran')) {
                $table->decimal('deviasi_setoran', 20, 2)->default(0);
            }
            if (! Schema::hasColumn('eku_transactions', 'deviasi_penarikan')) {
                $table->decimal('deviasi_penarikan', 20, 2)->default(0);
            }
            if (! Schema::hasColumn('eku_transactions', 'realisasi_uploaded_at')) {
                $table->timestamp('realisasi_uploaded_at')->nullable();
            }
        });

        Schema::table('eku_transaction_details', function (Blueprint $table) {
            if (! Schema::hasColumn('eku_transaction_details', 'realisasi_setoran')) {
                $table->decimal('realisasi_setoran', 20, 2)->default(0);
            }
            if (! Schema::hasColumn('eku_transaction_details', 'realisasi_penarikan')) {
                $table->decimal('realisasi_penarikan', 20, 2)->default(0);
            }
            if (! Schema::hasColumn('eku_transaction_details', 'deviasi_setoran')) {
                $table->decimal('deviasi_setoran', 20, 2)->default(0);
            }
            if (! Schema::hasColumn('eku_transaction_details', 'deviasi_penarikan')) {
                $table->decimal('deviasi_penarikan', 20, 2)->default(0);
            }
        });
    }

    public function down(): void
    {
        Schema::table('eku_transactions', function (Blueprint $table) {
            $columns = [
                'file_realisasi_setoran',
                'file_realisasi_penarikan',
                'file_realisasi_setoran_original',
                'file_realisasi_penarikan_original',
                'total_realisasi_setoran',
                'total_realisasi_penarikan',
                'deviasi_setoran',
                'deviasi_penarikan',
                'realisasi_uploaded_at',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('eku_transactions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('eku_transaction_details', function (Blueprint $table) {
            $columns = [
                'realisasi_setoran',
                'realisasi_penarikan',
                'deviasi_setoran',
                'deviasi_penarikan',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('eku_transaction_details', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
