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
     * @param string $ikon Nama ikon heroicon (format Filament).
     * @param string $warna Warna: primary|success|warning|danger|info|gray.
     * @param string|null $url Link tujuan saat notifikasi diklik.
     * @param bool $kirimEmail Apakah notifikasi juga dikirim melalui email.
     * @param string|null $emailBody Isi email. Jika kosong, menggunakan $pesan.
     * @param string|null $emailAction Label tombol pada email.
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
     * Notification untuk Filament/database.
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
     * Simpan notification ke database.
     */
    public function toDatabase(object $notifiable): array
    {
        return $this->buildFilamentNotification()->getDatabaseMessage();
    }

    /**
     * Email notification menggunakan custom Blade template.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject($this->judul)
            ->view('emails.eku-notification', [
                'judul' => $this->judul,
                'pesan' => $this->emailBody ?? $this->pesan,
                'url' => $this->url,
                'emailAction' => $this->emailAction ?? 'Lihat Detail',
                'namaPenerima' => $notifiable->name ?? 'Pengguna',
                'bank' => $notifiable->bank?->name ?? null,
            ]);
    }
}
