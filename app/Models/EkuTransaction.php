<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EkuTransaction extends Model
{
    protected $fillable = [
        'bank_id',
        'user_id',
        'file_setoran',
        'file_penarikan',
        'kertas_100k', 'kertas_50k', 'kertas_20k', 'kertas_10k', 'kertas_5k', 'kertas_2k', 'kertas_1k',
        'logam_1k', 'logam_500', 'logam_200', 'logam_100',
        'total_nominal',
        'status',
        'catatan',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'total_nominal' => 'decimal:2',
            'approved_at' => 'datetime',
        ];
    }

    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
