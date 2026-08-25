<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Pengingat deadline EKU otomatis setiap hari pukul 07:00
Schedule::command('eku:kirim-pengingat-deadline')->dailyAt('07:00');

// Schedule::command('eku:kirim-pengingat-deadline')->everyMinute();
