<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('banks', function (Blueprint $table) {
            $table->decimal('batasan_setoran', 20, 2)->nullable()->after('is_active');
            $table->decimal('batasan_penarikan', 20, 2)->nullable()->after('batasan_setoran');
        });
    }

    public function down(): void
    {
        Schema::table('banks', function (Blueprint $table) {
            $table->dropColumn(['batasan_setoran', 'batasan_penarikan']);
        });
    }
};
