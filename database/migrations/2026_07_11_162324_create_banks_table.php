<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banks', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // Contoh: 'SULSELBAR', 'BCA'
            $table->string('name'); // Contoh: 'Bank Sulselbar'
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
        $table->foreign('bank_id')->references('id')->on('banks')->nullOnDelete();
    });
    }

    public function down(): void
    {
        Schema::dropIfExists('banks');
    }
};
