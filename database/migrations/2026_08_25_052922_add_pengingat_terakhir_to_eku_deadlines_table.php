<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eku_deadlines', function (Blueprint $table) {
            if (! Schema::hasColumn('eku_deadlines', 'pengingat_terakhir')) {
                // Menyimpan jumlah hari-tersisa terakhir kali reminder "deadline
                // sudah dekat" dikirim (misal "3" atau "1"), supaya scheduler
                // harian tidak mengirim notifikasi yang sama berkali-kali.
                $table->string('pengingat_terakhir')->nullable()->after('keterangan');
            }
        });
    }

    public function down(): void
    {
        Schema::table('eku_deadlines', function (Blueprint $table) {
            if (Schema::hasColumn('eku_deadlines', 'pengingat_terakhir')) {
                $table->dropColumn('pengingat_terakhir');
            }
        });
    }
};
