<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eku_transaction_realisasis', function (Blueprint $table) {
            $table->id();

            $table->foreignId('eku_transaction_id')
                ->constrained('eku_transactions')
                ->cascadeOnDelete();

            // File Excel realisasi yang diunggah User BI (format sama dengan
            // Template Kerja EKU yang dipakai bank saat pengajuan/forecast).
            $table->string('file_setoran')->nullable();
            $table->string('file_penarikan')->nullable();

            // TOTAL per jenis & keseluruhan, hasil rekap dari
            // eku_transaction_realisasi_details (lihat recalculateTotals()).
            $table->decimal('total_setoran', 20, 2)->default(0);
            $table->decimal('total_penarikan', 20, 2)->default(0);
            $table->decimal('total_nominal', 20, 2)->default(0);

            $table->foreignId('input_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('input_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eku_transaction_realisasis');
    }
};