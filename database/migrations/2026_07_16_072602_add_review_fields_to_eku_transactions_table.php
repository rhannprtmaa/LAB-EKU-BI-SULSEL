<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eku_transactions', function (Blueprint $table) {
            // Menyimpan salinan file ASLI dari bank saat pertama kali submit,
            // agar bisa dibandingkan dengan file yang sudah direvisi/diedit oleh User BI.
            $table->string('file_setoran_original')->nullable()->after('file_setoran');
            $table->string('file_penarikan_original')->nullable()->after('file_penarikan');
            $table->string('file_lampiran_original')->nullable()->after('file_lampiran');

            // Penanda apakah data/file sudah pernah diubah oleh User BI saat proses review.
            $table->boolean('is_edited_by_bi')->default(false)->after('catatan');
        });
    }

    public function down(): void
    {
        Schema::table('eku_transactions', function (Blueprint $table) {
            $table->dropColumn([
                'file_setoran_original',
                'file_penarikan_original',
                'file_lampiran_original',
                'is_edited_by_bi',
            ]);
        });
    }
};
