<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EkuTemplate extends Model
{
    protected $guarded = [];

    public const JENIS_SETORAN = 'Setoran';
    public const JENIS_PENARIKAN = 'Penarikan';

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public static function current(string $jenis): ?self
    {
        return static::query()->where('jenis', $jenis)->latest('id')->first();
    }
}
