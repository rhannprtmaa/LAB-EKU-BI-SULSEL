<?php

namespace App\Notifications;

use Filament\Actions\Action as FilamentNotificationAction;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification as BaseNotification;

class AppNotification extends BaseNotification
{
    /**
     * @param string $judul Judul singkat notifikasi.
     * @param string $pesan Isi/deskripsi notifikasi.
     * @param string $ikon Nama ikon heroicon.
     * @param string $warna Warna: primary|success|warning|danger|info|gray.
     * @param string|null $url Link tujuan saat notifikasi diklik.
     * @param bool $kirimEmail Apakah event ini juga dikirim melalui email.
     * @param string|null $emailBody Isi email. Jika null, menggunakan $pesan.
     * @param string|null $emailAction Label tombol aksi di email.
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

    /**
     * Channel notifikasi.
     *
     * Untuk testing kita gunakan database + mail secara langsung
     * tanpa queue.
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($this->kirimEmail) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Membuat format notifikasi yang digunakan oleh Filament.
     */
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

    /**
     * Data yang disimpan ke tabel notifications.
     */
    public function toDatabase(object $notifiable): array
    {
        return $this->buildFilamentNotification()->getDatabaseMessage();
    }

    /**
     * Format email.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage())
            ->subject($this->judul)
            ->greeting('Halo, ' . ($notifiable->name ?? '') . '!')
            ->line($this->emailBody ?? $this->pesan);

        if ($this->url) {
            $mail->action(
                $this->emailAction ?? 'Buka Aplikasi',
                $this->url
            );
        }

        return $mail->line(
            'Email ini dikirim otomatis oleh Sistem LAB EKU BI Sulsel, mohon tidak membalas email ini.'
        );
    }
}
