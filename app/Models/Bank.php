<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bank extends Model
{
    protected $fillable = ['code', 'name', 'is_active'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
