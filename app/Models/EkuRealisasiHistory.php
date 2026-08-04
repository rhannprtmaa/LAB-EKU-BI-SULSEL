<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EkuRealisasiHistory extends Model
{
    protected $guarded = [];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(EkuTransaction::class, 'eku_transaction_id');
    }
}
