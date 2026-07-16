<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eku_transaction_details', function (Blueprint $table) {
            $table->id();
            // Menghubungkan ke tabel induk (transaksi utama)
            $table->foreignId('eku_transaction_id')->constrained('eku_transactions')->cascadeOnDelete();

            $table->string('bulan'); // Akan diisi: Januari, Februari, dst.
            $table->string('jenis_file'); // Akan diisi: 'Setoran' atau 'Penarikan'

            // --- RINCIAN UANG KERTAS (Per Bulan) ---
            $table->decimal('kertas_100k', 20, 2)->default(0);
            $table->decimal('kertas_50k', 20, 2)->default(0);
            $table->decimal('kertas_20k', 20, 2)->default(0);
            $table->decimal('kertas_10k', 20, 2)->default(0);
            $table->decimal('kertas_5k', 20, 2)->default(0);
            $table->decimal('kertas_2k', 20, 2)->default(0);
            $table->decimal('kertas_1k', 20, 2)->default(0);

            // --- RINCIAN UANG LOGAM (Per Bulan) ---
            $table->decimal('logam_1k', 20, 2)->default(0);
            $table->decimal('logam_500', 20, 2)->default(0);
            $table->decimal('logam_200', 20, 2)->default(0);
            $table->decimal('logam_100', 20, 2)->default(0);

            $table->decimal('subtotal', 20, 2)->default(0); // Total nominal di bulan tersebut

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eku_transaction_details');
    }
};
