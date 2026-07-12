<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Bank;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Bank Sample
        $bankSulselbar = Bank::create([
            'code' => 'SULSELBAR',
            'name' => 'Bank Sulselbar',
            'is_active' => true,
        ]);

        Bank::create([
            'code' => 'BCA',
            'name' => 'Bank BCA',
            'is_active' => true,
        ]);

        // 2. Akun Admin BI
        User::create([
            'name' => 'Admin BI Sulsel',
            'email' => 'admin@bi.go.id',
            'password' => Hash::make('password123'),
            'role' => 'admin_bi',
            'is_active' => true,
        ]);

        // 3. Akun User BI Operational
        User::create([
            'name' => 'User BI Operational',
            'email' => 'user@bi.go.id',
            'password' => Hash::make('password123'),
            'role' => 'user_bi',
            'is_active' => true,
        ]);

        // 4. Akun User Perbankan
        User::create([
            'name' => 'Petugas Bank Sulselbar',
            'email' => 'eku@banksulselbar.co.id',
            'password' => Hash::make('password123'),
            'role' => 'user_perbankan',
            'bank_id' => $bankSulselbar->id,
            'is_active' => true,
        ]);
    }
}
