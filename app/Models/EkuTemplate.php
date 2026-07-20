<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EkuTemplate extends Model
{
    protected $guarded = [];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public static function current(): ?self
    {
        return static::query()->latest('id')->first();
    }
}
