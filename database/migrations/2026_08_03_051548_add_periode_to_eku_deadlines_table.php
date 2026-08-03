<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eku_transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('eku_transactions', 'batasan_periode')) {
                $table->string('batasan_periode')->nullable()->after('periode');
            }
        });
    }

    public function down(): void
    {
        Schema::table('eku_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('eku_transactions', 'batasan_periode')) {
                $table->dropColumn('batasan_periode');
            }
        });
    }
};
