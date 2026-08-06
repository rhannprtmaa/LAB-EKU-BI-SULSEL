<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bank extends Model
{
    protected $fillable = ['code', 'name', 'is_active', 'batasan_setoran', 'batasan_penarikan'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function ekuTransactions(): HasMany
    {
        return $this->hasMany(EkuTransaction::class);
    }
}
