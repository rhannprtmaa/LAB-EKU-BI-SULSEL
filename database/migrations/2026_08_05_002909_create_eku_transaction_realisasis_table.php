<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eku_transaction_realisasi_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eku_transaction_realisasi_id');
            $table->foreign('eku_transaction_realisasi_id', 'fk_realisasi_detail_realisasi_id')
                ->references('id')->on('eku_transaction_realisasis')->cascadeOnDelete();

            $table->string('bulan'); // Januari, Februari, dst.
            $table->string('jenis_file'); // 'Setoran' atau 'Penarikan'

            $table->decimal('kertas_100k', 20, 2)->default(0);
            $table->decimal('kertas_50k', 20, 2)->default(0);
            $table->decimal('kertas_20k', 20, 2)->default(0);
            $table->decimal('kertas_10k', 20, 2)->default(0);
            $table->decimal('kertas_5k', 20, 2)->default(0);
            $table->decimal('kertas_2k', 20, 2)->default(0);
            $table->decimal('kertas_1k', 20, 2)->default(0);

            $table->decimal('logam_1k', 20, 2)->default(0);
            $table->decimal('logam_500', 20, 2)->default(0);
            $table->decimal('logam_200', 20, 2)->default(0);
            $table->decimal('logam_100', 20, 2)->default(0);

            $table->decimal('subtotal', 20, 2)->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eku_transaction_realisasi_details');
    }
};
