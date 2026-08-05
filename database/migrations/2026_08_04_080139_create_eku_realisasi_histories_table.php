<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eku_realisasi_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eku_transaction_id')->constrained('eku_transactions')->cascadeOnDelete();
            $table->string('file_realisasi_setoran')->nullable();
            $table->string('file_realisasi_penarikan')->nullable();
            $table->decimal('total_realisasi_setoran', 20, 2)->default(0);
            $table->decimal('total_realisasi_penarikan', 20, 2)->default(0);
            $table->decimal('deviasi_setoran', 20, 2)->default(0);
            $table->decimal('deviasi_penarikan', 20, 2)->default(0);
            $table->timestamp('uploaded_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eku_realisasi_histories');
    }
};
