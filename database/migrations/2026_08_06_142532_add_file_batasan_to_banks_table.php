<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('banks', function (Blueprint $table) {
            $table->string('file_batasan_setoran')->nullable()->after('batasan_penarikan');
            $table->string('file_batasan_setoran_nama_asli')->nullable()->after('file_batasan_setoran');
            $table->string('file_batasan_penarikan')->nullable()->after('file_batasan_setoran_nama_asli');
            $table->string('file_batasan_penarikan_nama_asli')->nullable()->after('file_batasan_penarikan');
        });
    }

    public function down(): void
    {
        Schema::table('banks', function (Blueprint $table) {
            $table->dropColumn([
                'file_batasan_setoran', 'file_batasan_setoran_nama_asli',
                'file_batasan_penarikan', 'file_batasan_penarikan_nama_asli',
            ]);
        });
    }
};
