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
            $table->foreignId('bank_id')->constrained('banks')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // Kolom baru yang kurang
            $table->string('periode');
            $table->string('batasan_periode')->nullable();

            $table->string('file_setoran')->nullable();
            $table->string('file_penarikan')->nullable();
            $table->string('file_lampiran')->nullable(); // Kolom baru file lampiran

            // Pecahan Uang Kertas (Grand Total)
            $table->decimal('kertas_100k', 20, 2)->default(0);
            $table->decimal('kertas_50k', 20, 2)->default(0);
            $table->decimal('kertas_20k', 20, 2)->default(0);
            $table->decimal('kertas_10k', 20, 2)->default(0);
            $table->decimal('kertas_5k', 20, 2)->default(0);
            $table->decimal('kertas_2k', 20, 2)->default(0);
            $table->decimal('kertas_1k', 20, 2)->default(0);

            // Pecahan Uang Logam (Grand Total)
            $table->decimal('logam_1k', 20, 2)->default(0);
            $table->decimal('logam_500', 20, 2)->default(0);
            $table->decimal('logam_200', 20, 2)->default(0);
            $table->decimal('logam_100', 20, 2)->default(0);

            $table->decimal('total_nominal', 20, 2)->default(0);
            $table->string('status')->default('Menunggu');
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
