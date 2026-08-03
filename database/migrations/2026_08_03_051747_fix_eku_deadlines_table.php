<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Tambahkan kolom created_by yang dipakai halaman "Management EKU"
        //    tapi belum pernah ada di tabel ini — inilah yang bikin simpan
        //    batas waktu selalu gagal (error kolom tidak ditemukan) dan
        //    datanya tidak pernah benar-benar masuk ke database.
        Schema::table('eku_deadlines', function (Blueprint $table) {
            if (! Schema::hasColumn('eku_deadlines', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('keterangan')
                    ->constrained('users')->nullOnDelete();
            }
        });

        // 2) Kolom "periode" tadinya wajib diisi + unique (didesain untuk
        //    batas waktu per-periode). Sekarang halaman "Management EKU"
        //    menyimpan satu batas waktu GLOBAL tanpa memilih periode, jadi
        //    kolom ini perlu boleh kosong dan tidak lagi unique.
        if (Schema::hasColumn('eku_deadlines', 'periode')) {
            $indexes = collect(DB::select('SHOW INDEX FROM eku_deadlines'))
                ->pluck('Key_name')
                ->unique();

            if ($indexes->contains('eku_deadlines_periode_unique')) {
                Schema::table('eku_deadlines', function (Blueprint $table) {
                    $table->dropUnique('eku_deadlines_periode_unique');
                });
            }

            DB::statement('ALTER TABLE eku_deadlines MODIFY periode VARCHAR(255) NULL');
        }
    }

    public function down(): void
    {
        Schema::table('eku_deadlines', function (Blueprint $table) {
            if (Schema::hasColumn('eku_deadlines', 'created_by')) {
                $table->dropConstrainedForeignId('created_by');
            }
        });
    }
};
