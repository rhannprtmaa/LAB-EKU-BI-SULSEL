<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EkuTransactionDetail extends Model
{
    // Mengizinkan semua kolom diisi secara otomatis oleh sistem
    protected $guarded = [];

    // Relasi balik ke Transaksi Utama
    public function ekuTransaction(): BelongsTo
    {
        return $this->belongsTo(EkuTransaction::class);
    }
}
