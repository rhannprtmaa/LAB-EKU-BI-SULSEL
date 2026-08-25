<?php

namespace App\Services;

use App\Filament\Resources\EkuDeadlines\EkuDeadlineResource;
use App\Filament\Resources\EkuTransactions\EkuTransactionResource;
use App\Filament\Resources\RealisasiEkus\RealisasiEkuResource;
use App\Filament\Pages\KnowledgeCenter;
use App\Models\Bank;
use App\Models\EkuDeadline;
use App\Models\EkuTemplate;
use App\Models\EkuTransaction;
use App\Models\EkuTransactionRealisasi;
use App\Models\KnowledgePost;
use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class NotifikasiService
{
    // ---------------------------------------------------------------
    // Helper penerima
    // ---------------------------------------------------------------

    /** Semua user BI (admin_bi + user_bi) yang aktif. */
    protected static function userBi(): Collection
    {
        return User::query()
            ->whereIn('role', ['admin_bi', 'user_bi'])
            ->where('is_active', true)
            ->get();
    }

    /** Semua user perbankan aktif milik satu bank tertentu. */
    protected static function userBank(?int $bankId): Collection
    {
        if (! $bankId) {
            return new Collection();
        }

        return User::query()
            ->where('role', 'user_perbankan')
            ->where('bank_id', $bankId)
            ->where('is_active', true)
            ->get();
    }

    /** Semua user perbankan aktif, dari semua bank. */
    protected static function semuaUserBank(): Collection
    {
        return User::query()
            ->where('role', 'user_perbankan')
            ->where('is_active', true)
            ->get();
    }

    /** Kirim ke gabungan koleksi user tanpa duplikat. */
    protected static function kirim(Collection $penerima, AppNotification $notifikasi): void
    {
        $penerima = $penerima->unique('id')->filter();

        if ($penerima->isEmpty()) {
            return;
        }

        NotificationFacade::send($penerima, $notifikasi);
    }

    // ---------------------------------------------------------------
    // 1. Deadline baru dibuat
    // ---------------------------------------------------------------
    public static function deadlineBaruDibuat(EkuDeadline $deadline): void
    {
        $tanggal = $deadline->batas_waktu?->locale('id')->translatedFormat('d F Y');

        self::kirim(self::semuaUserBank(), new AppNotification(
            judul: 'Batas Waktu Pengajuan EKU Baru Ditetapkan',
            pesan: "Batas waktu pengajuan EKU baru ditetapkan pada {$tanggal}." . ($deadline->keterangan ? " Keterangan: {$deadline->keterangan}." : ''),
            ikon: 'heroicon-o-calendar-days',
            warna: 'info',
            url: EkuTransactionResource::getUrl('index'),
            kirimEmail: true,
            emailAction: 'Buka Pengajuan EKU',
        ));
    }

    // ---------------------------------------------------------------
    // 2. Deadline diubah
    // ---------------------------------------------------------------
    public static function deadlineDiubah(EkuDeadline $deadline): void
    {
        $tanggal = $deadline->batas_waktu?->locale('id')->translatedFormat('d F Y');

        self::kirim(self::semuaUserBank(), new AppNotification(
            judul: 'Batas Waktu Pengajuan EKU Diperbarui',
            pesan: "Batas waktu pengajuan EKU diperbarui menjadi {$tanggal}." . ($deadline->keterangan ? " Keterangan: {$deadline->keterangan}." : ''),
            ikon: 'heroicon-o-calendar-days',
            warna: 'warning',
            url: EkuTransactionResource::getUrl('index'),
            kirimEmail: true,
            emailAction: 'Buka Pengajuan EKU',
        ));
    }

    // ---------------------------------------------------------------
    // 3. Deadline sudah dekat (dipanggil dari scheduled command)
    // ---------------------------------------------------------------
    public static function deadlineSudahDekat(EkuDeadline $deadline, int $sisaHari): void
    {
        $tanggal = $deadline->batas_waktu?->locale('id')->translatedFormat('d F Y');
        $label = $sisaHari <= 0 ? 'HARI INI adalah batas waktu terakhir' : "tersisa {$sisaHari} hari lagi";

        self::kirim(self::semuaUserBank(), new AppNotification(
            judul: 'Pengingat: Batas Waktu Pengajuan EKU Sudah Dekat',
            pesan: "Batas waktu pengajuan EKU jatuh pada {$tanggal} ({$label}). Segera lengkapi pengajuan Anda.",
            ikon: 'heroicon-o-exclamation-triangle',
            warna: 'danger',
            url: EkuTransactionResource::getUrl('index'),
            kirimEmail: true,
            emailAction: 'Lengkapi Pengajuan Sekarang',
        ));
    }

    // ---------------------------------------------------------------
    // 4. Pengajuan disetujui (bisa dengan catatan / hasil penyesuaian batasan)
    // ---------------------------------------------------------------
    public static function pengajuanDisetujui(EkuTransaction $transaksi, ?string $catatan = null): void
    {
        $pesan = "Pengajuan EKU periode {$transaksi->periode} atas nama " . ($transaksi->bank?->name ?? 'bank Anda') . ' telah DISETUJUI oleh Bank Indonesia.';

        if ($catatan) {
            $pesan .= " Catatan dari BI: {$catatan}";
        }

        self::kirim(self::userBank($transaksi->bank_id), new AppNotification(
            judul: 'Pengajuan EKU Disetujui',
            pesan: $pesan,
            ikon: 'heroicon-o-check-circle',
            warna: 'success',
            url: EkuTransactionResource::getUrl('view', ['record' => $transaksi]),
            kirimEmail: true,
            emailAction: 'Lihat Pengajuan',
        ));
    }

    // ---------------------------------------------------------------
    // 5. Pengajuan ditolak / perlu revisi
    // ---------------------------------------------------------------
    public static function pengajuanPerluRevisi(EkuTransaction $transaksi, string $catatan): void
    {
        self::kirim(self::userBank($transaksi->bank_id), new AppNotification(
            judul: 'Pengajuan EKU Perlu Revisi',
            pesan: "Pengajuan EKU periode {$transaksi->periode} dikembalikan untuk direvisi. Catatan dari BI: {$catatan}",
            ikon: 'heroicon-o-arrow-uturn-left',
            warna: 'warning',
            url: EkuTransactionResource::getUrl('view', ['record' => $transaksi]),
            kirimEmail: true,
            emailAction: 'Perbaiki Pengajuan',
        ));
    }

    public static function pengajuanDitolak(EkuTransaction $transaksi, string $catatan): void
    {
        self::kirim(self::userBank($transaksi->bank_id), new AppNotification(
            judul: 'Pengajuan EKU Ditolak',
            pesan: "Pengajuan EKU periode {$transaksi->periode} DITOLAK oleh Bank Indonesia. Catatan: {$catatan}",
            ikon: 'heroicon-o-x-circle',
            warna: 'danger',
            url: EkuTransactionResource::getUrl('view', ['record' => $transaksi]),
            kirimEmail: true,
            emailAction: 'Lihat Pengajuan',
        ));
    }

    // ---------------------------------------------------------------
    // 6. Batasan EKU bank diubah
    // ---------------------------------------------------------------
    public static function batasanEkuDiubah(Bank $bank, string $ringkasan = ''): void
    {
        self::kirim(self::userBank($bank->id), new AppNotification(
            judul: 'Batasan EKU Bank Diperbarui',
            pesan: 'Batasan EKU untuk ' . $bank->name . ' telah diperbarui oleh Bank Indonesia.' . ($ringkasan ? " {$ringkasan}" : ''),
            ikon: 'heroicon-o-adjustments-horizontal',
            warna: 'warning',
            url: EkuTransactionResource::getUrl('index'),
            kirimEmail: true,
            emailAction: 'Buka Pengajuan EKU',
        ));
    }

    // ---------------------------------------------------------------
    // 7. Template EKU berubah
    // ---------------------------------------------------------------
    public static function templateBerubah(array $jenisBerubah): void
    {
        $daftar = implode(', ', $jenisBerubah);

        self::kirim(self::semuaUserBank(), new AppNotification(
            judul: 'Template Kerja EKU Diperbarui',
            pesan: "Template EKU ({$daftar}) telah diperbarui oleh Bank Indonesia. Gunakan template terbaru untuk pengajuan Anda.",
            ikon: 'heroicon-o-document-arrow-up',
            warna: 'info',
            url: EkuTransactionResource::getUrl('index'),
            kirimEmail: false,
        ));
    }

    // ---------------------------------------------------------------
    // 8. Pengajuan berhasil dikirim (bank -> BI)
    // ---------------------------------------------------------------
    public static function pengajuanBerhasilDikirim(EkuTransaction $transaksi): void
    {
        self::kirim(self::userBi(), new AppNotification(
            judul: 'Pengajuan EKU Baru Masuk',
            pesan: ($transaksi->bank?->name ?? 'Sebuah bank') . " mengirim pengajuan EKU untuk periode {$transaksi->periode}. Segera lakukan review.",
            ikon: 'heroicon-o-inbox-arrow-down',
            warna: 'primary',
            url: EkuTransactionResource::getUrl('view', ['record' => $transaksi]),
            kirimEmail: true,
            emailAction: 'Review Pengajuan',
        ));
    }

    // ---------------------------------------------------------------
    // 9. Realisasi berhasil diupload (bank -> BI)
    // ---------------------------------------------------------------
    public static function realisasiBerhasilDiupload(EkuTransactionRealisasi $realisasi): void
    {
        $transaksi = $realisasi->ekuTransaction;

        if (! $transaksi) {
            return;
        }

        self::kirim(self::userBi(), new AppNotification(
            judul: 'Realisasi EKU Baru Diupload',
            pesan: ($transaksi->bank?->name ?? 'Sebuah bank') . " mengupload realisasi EKU untuk periode {$transaksi->periode}.",
            ikon: 'heroicon-o-chart-bar-square',
            warna: 'primary',
            url: RealisasiEkuResource::getUrl('view', ['record' => $transaksi]),
            kirimEmail: true,
            emailAction: 'Lihat Realisasi',
        ));
    }

    // ---------------------------------------------------------------
    // 10. Informasi / pengumuman biasa
    // ---------------------------------------------------------------
    public static function pengumuman(KnowledgePost $post): void
    {
        $penerima = self::semuaUserBank()->merge(self::userBi());

        self::kirim($penerima, new AppNotification(
            judul: 'Pengumuman Baru: ' . $post->judul,
            pesan: $post->ringkasan ?? 'Ada informasi/pengumuman baru dari Bank Indonesia. Klik untuk membaca selengkapnya.',
            ikon: 'heroicon-o-megaphone',
            warna: 'info',
            url: KnowledgeCenter::getUrl(),
            kirimEmail: false,
        ));
    }
}
