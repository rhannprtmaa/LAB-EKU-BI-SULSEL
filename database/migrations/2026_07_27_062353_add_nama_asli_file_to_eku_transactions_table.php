<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eku_transactions', function (Blueprint $table) {
            $table->string('file_setoran_nama_asli')->nullable()->after('file_setoran');
            $table->string('file_penarikan_nama_asli')->nullable()->after('file_penarikan');
            $table->string('file_lampiran_nama_asli')->nullable()->after('file_lampiran');
        });
    }

    public function down(): void
    {
        Schema::table('eku_transactions', function (Blueprint $table) {
            $table->dropColumn([
                'file_setoran_nama_asli',
                'file_penarikan_nama_asli',
                'file_lampiran_nama_asli',
            ]);
        });
    }
};
