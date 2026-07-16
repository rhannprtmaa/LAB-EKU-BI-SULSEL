<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class CurrentUser
{
    /**
     * Ambil user yang sedang login dengan tipe App\Models\User yang eksplisit.
     * Auth::user() secara default bertipe Authenticatable, sehingga IDE/PHPStan
     * tidak mengenali method custom seperti isUserBi(), isAdminBi(), dst.
     * Helper ini menyelesaikan itu di satu tempat saja.
     */
    public static function get(): ?User
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user;
    }
}
