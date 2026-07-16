<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eku_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // File Excel yang diunggah (bisa salah satu atau keduanya)
            $table->string('file_setoran')->nullable();
            $table->string('file_penarikan')->nullable();

            // --- RINCIAN UANG KERTAS ---
            $table->decimal('kertas_100k', 20, 2)->default(0);
            $table->decimal('kertas_50k', 20, 2)->default(0);
            $table->decimal('kertas_20k', 20, 2)->default(0);
            $table->decimal('kertas_10k', 20, 2)->default(0);
            $table->decimal('kertas_5k', 20, 2)->default(0);
            $table->decimal('kertas_2k', 20, 2)->default(0);
            $table->decimal('kertas_1k', 20, 2)->default(0);

            // --- RINCIAN UANG LOGAM ---
            $table->decimal('logam_1k', 20, 2)->default(0);
            $table->decimal('logam_500', 20, 2)->default(0);
            $table->decimal('logam_200', 20, 2)->default(0);
            $table->decimal('logam_100', 20, 2)->default(0);

            // TOTAL KESELURUHAN & STATUS
            $table->decimal('total_nominal', 20, 2)->default(0);
            $table->enum('status', ['Menunggu', 'Disetujui', 'Ditolak'])->default('Menunggu');
            $table->text('catatan')->nullable();

            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eku_transactions');
    }
};
