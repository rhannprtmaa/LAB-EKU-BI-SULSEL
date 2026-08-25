<?php

namespace App\Notifications;

use Filament\Actions\Action as FilamentNotificationAction;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification as BaseNotification;

class AppNotification extends BaseNotification implements ShouldQueue
{
    use Queueable;

    /**
     * @param string $judul Judul singkat notifikasi.
     * @param string $pesan Isi/deskripsi notifikasi.
     * @param string $ikon Nama ikon heroicon (format Filament), misal 'heroicon-o-bell'.
     * @param string $warna Warna: primary|success|warning|danger|info|gray.
     * @param string|null $url Link tujuan saat notifikasi diklik (opsional).
     * @param bool $kirimEmail Apakah event ini juga dikirim ke Gmail (channel mail).
     * @param string|null $emailBody Isi email (kalau kosong, pakai $pesan).
     * @param string|null $emailAction Label tombol aksi di email (misal "Lihat Pengajuan").
     */
    public function __construct(
        public string $judul,
        public string $pesan,
        public string $ikon = 'heroicon-o-bell-alert',
        public string $warna = 'info',
        public ?string $url = null,
        public bool $kirimEmail = false,
        public ?string $emailBody = null,
        public ?string $emailAction = null,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($this->kirimEmail) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    protected function buildFilamentNotification(): FilamentNotification
    {
        $notification = FilamentNotification::make()
            ->title($this->judul)
            ->body($this->pesan)
            ->icon($this->ikon)
            ->iconColor($this->warna)
            ->color($this->warna)
            ->persistent();

        if ($this->url) {
            $notification->actions([
                FilamentNotificationAction::make('lihat')
                    ->label('Lihat Detail')
                    ->url($this->url)
                    ->button(),
            ]);
        }

        return $notification;
    }

    public function toDatabase(object $notifiable): array
    {
        return $this->buildFilamentNotification()->getDatabaseMessage();
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage())
            ->subject($this->judul)
            ->greeting('Halo, ' . ($notifiable->name ?? '') . '!')
            ->line($this->emailBody ?? $this->pesan);

        if ($this->url) {
            $mail->action($this->emailAction ?? 'Buka Aplikasi', $this->url);
        }

        return $mail->line('Email ini dikirim otomatis oleh Sistem LAB EKU BI Sulsel, mohon tidak membalas email ini.');
    }
}
