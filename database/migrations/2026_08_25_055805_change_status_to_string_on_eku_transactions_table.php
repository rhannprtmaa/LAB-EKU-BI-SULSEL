<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            // SQLite tidak punya ALTER COLUMN TYPE; kolomnya sudah berupa
            // CHECK constraint dari definisi enum saat table dibuat, jadi
            // tidak perlu diapa-apakan lagi di driver ini untuk keperluan
            // development lokal.
            return;
        }

        DB::statement("ALTER TABLE eku_transactions MODIFY status VARCHAR(30) NOT NULL DEFAULT 'Menunggu'");
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE eku_transactions MODIFY status ENUM('Menunggu','Disetujui','Ditolak') NOT NULL DEFAULT 'Menunggu'");
    }
};
