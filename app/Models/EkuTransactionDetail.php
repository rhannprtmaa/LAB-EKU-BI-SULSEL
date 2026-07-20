<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EkuTransactionDetail extends Model
{
    protected $guarded = [];

    public function ekuTransaction(): BelongsTo
    {
        return $this->belongsTo(EkuTransaction::class);
    }
    
    public function recalculateSubtotal(): void
    {
        $this->subtotal = $this->kertas_100k + $this->kertas_50k + $this->kertas_20k
            + $this->kertas_10k + $this->kertas_5k + $this->kertas_2k + $this->kertas_1k
            + $this->logam_1k + $this->logam_500 + $this->logam_200 + $this->logam_100;

        $this->saveQuietly();
    }
}
