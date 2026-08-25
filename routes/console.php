<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Cek tiap hari jam 07:00 apakah batas waktu pengajuan EKU sudah dekat
// (H-3 / H-1), lalu kirim notifikasi in-app + Gmail ke semua bank.
// PENTING: scheduler Laravel ini hanya jalan kalau cron server sudah
// diarahkan ke `php artisan schedule:run` tiap menit (lihat catatan
// di respons chat untuk cara setup-nya).
Schedule::command('eku:kirim-pengingat-deadline')->dailyAt('07:00');
