<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\EkuExcelImport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Filament\Notifications\Notification;

class EkuTransaction extends Model
{
    protected $guarded = [];

    public const STATUS_MENUNGGU = 'Menunggu';
    public const STATUS_DISETUJUI = 'Disetujui';
    public const STATUS_REVISI = 'Perlu Revisi';
    public const STATUS_DITOLAK = 'Ditolak';

    public static function statusOptions(): array
    {
        return [
            self::STATUS_MENUNGGU => 'Menunggu Review',
            self::STATUS_DISETUJUI => 'Disetujui',
            self::STATUS_REVISI => 'Perlu Revisi',
            self::STATUS_DITOLAK => 'Ditolak',
        ];
    }

    protected function casts(): array
    {
        return [
            'total_nominal' => 'decimal:2',
            'approved_at' => 'datetime',
            'is_edited_by_bi' => 'boolean',
        ];
    }

   protected static function booted()
    {
        static::creating(function ($transaction) {
            $transaction->file_setoran_original ??= $transaction->file_setoran;
            $transaction->file_penarikan_original ??= $transaction->file_penarikan;
            $transaction->file_lampiran_original ??= $transaction->file_lampiran;
            $transaction->status ??= self::STATUS_MENUNGGU;
        });

        static::updating(function ($transaction) {
            $fileBerubah = $transaction->isDirty(['file_setoran', 'file_penarikan'])
                && (
                    $transaction->file_setoran !== $transaction->file_setoran_original
                    || $transaction->file_penarikan !== $transaction->file_penarikan_original
                );

            if ($fileBerubah) {
                $transaction->is_edited_by_bi = true;
            }
        });

        static::saved(function ($transaction) {
            // Untuk record yang baru pertama kali dibuat (INSERT), Eloquent
            // tidak mengisi wasChanged() sama sekali (itu cuma disinkronkan
            // saat UPDATE oleh Laravel), jadi kita cek wasRecentlyCreated dulu
            // supaya pengajuan baru langsung diproses tanpa perlu eku:reparse.
            if ($transaction->wasRecentlyCreated) {
                $transaction->reprocessExcelFiles();

                return;
            }

            if (! $transaction->wasChanged(['file_setoran', 'file_penarikan'])) {
                return;
            }

            $transaction->reprocessExcelFiles();
        });
    }

    public function reprocessExcelFiles(): array
    {
        $transaction = $this;

        $transaction->details()->delete();

        $processExcel = function($filePath, $jenisFile) use ($transaction) {
            if (!$filePath) return 0;

            if (! Storage::disk('public')->exists($filePath)) return 0;

            $fullPath = Storage::disk('public')->path($filePath);

            $arrayData = Excel::toArray(new EkuExcelImport(), $fullPath);
            if (empty($arrayData) || empty($arrayData[0])) return 0;

            $sheet = $arrayData[0];
            $multiplier = 1000000;

            $clean = fn($val) => is_numeric($val)
                ? (float) $val
                : (float) str_replace(['.', ',', ' '], '', (string) $val);

            $kolomBulan = [
                3 => 'Januari', 4 => 'Februari', 5 => 'Maret', 6 => 'April',
                7 => 'Mei', 8 => 'Juni', 9 => 'Juli', 10 => 'Agustus',
                11 => 'September', 12 => 'Oktober', 13 => 'November', 14 => 'Desember',
            ];

            $petaKertas = [
                100000 => 'kertas_100k', 50000 => 'kertas_50k', 20000 => 'kertas_20k',
                10000 => 'kertas_10k', 5000 => 'kertas_5k', 2000 => 'kertas_2k', 1000 => 'kertas_1k',
            ];
            $petaLogam = [
                1000 => 'logam_1k', 500 => 'logam_500', 200 => 'logam_200', 100 => 'logam_100',
            ];

            $akumulasi = [];
            foreach ($kolomBulan as $namaBulan) {
                $akumulasi[$namaBulan] = [
                    'kertas_100k' => 0, 'kertas_50k' => 0, 'kertas_20k' => 0, 'kertas_10k' => 0,
                    'kertas_5k' => 0, 'kertas_2k' => 0, 'kertas_1k' => 0,
                    'logam_1k' => 0, 'logam_500' => 0, 'logam_200' => 0, 'logam_100' => 0,
                ];
            }

            $section = null;

            foreach ($sheet as $row) {
                $jenisUang = strtoupper(trim((string) ($row[1] ?? '')));
                $nominalRaw = $row[2] ?? null;

                if (str_contains($jenisUang, 'UANG KERTAS')) {
                    $section = 'kertas';
                } elseif (str_contains($jenisUang, 'UANG LOGAM')) {
                    $section = 'logam';
                }

                if (str_contains($jenisUang, 'TOTAL')) {
                    continue;
                }
                if (is_string($nominalRaw) && str_contains(strtoupper($nominalRaw), 'TOTAL')) {
                    continue;
                }

                if (! is_numeric($nominalRaw) || ! $section) {
                    continue;
                }

                $nominal = (int) $nominalRaw;
                $namaKolom = $section === 'kertas'
                    ? ($petaKertas[$nominal] ?? null)
                    : ($petaLogam[$nominal] ?? null);

                if (! $namaKolom) {
                    continue;
                }

                foreach ($kolomBulan as $colIdx => $namaBulan) {
                    $akumulasi[$namaBulan][$namaKolom] += $clean($row[$colIdx] ?? 0) * $multiplier;
                }
            }

            $baris = 0;
            foreach ($akumulasi as $namaBulan => $pecahan) {
                $transaction->details()->create(array_merge(
                    ['bulan' => $namaBulan, 'jenis_file' => $jenisFile, 'subtotal' => array_sum($pecahan)],
                    $pecahan
                ));

                $baris++;
            }

            return $baris;
        };

        $jumlahSetoran = $processExcel($transaction->file_setoran, 'Setoran');
        $jumlahPenarikan = $processExcel($transaction->file_penarikan, 'Penarikan');

        if ($jumlahSetoran === 0 && $jumlahPenarikan === 0 && ($transaction->file_setoran || $transaction->file_penarikan)) {
            Notification::make()
                ->title('Peringatan: Excel tidak berhasil dibaca')
                ->body('Sistem tidak menemukan struktur "UANG KERTAS" / "UANG LOGAM" di file yang diupload. Pastikan file mengikuti format Template Kerja EKU.')
                ->warning()
                ->persistent()
                ->send();
        }

        static::recalculateTotals($transaction->id);

        return ['setoran' => $jumlahSetoran, 'penarikan' => $jumlahPenarikan];
    }

    public static function recalculateTotals(int $transactionId): void
    {
        $totals = EkuTransactionDetail::query()
            ->where('eku_transaction_id', $transactionId)
            ->selectRaw('
                SUM(kertas_100k) as total_100k, SUM(kertas_50k) as total_50k,
                SUM(kertas_20k) as total_20k, SUM(kertas_10k) as total_10k,
                SUM(kertas_5k) as total_5k, SUM(kertas_2k) as total_2k,
                SUM(kertas_1k) as total_1k, SUM(logam_1k) as total_l1k,
                SUM(logam_500) as total_l500, SUM(logam_200) as total_l200,
                SUM(logam_100) as total_l100, SUM(subtotal) as grand_total
            ')->first();

        $totalPerJenis = EkuTransactionDetail::query()
            ->where('eku_transaction_id', $transactionId)
            ->selectRaw('jenis_file, SUM(subtotal) as total')
            ->groupBy('jenis_file')
            ->pluck('total', 'jenis_file');

        if ($totals) {
            DB::table('eku_transactions')->where('id', $transactionId)->update([
                'kertas_100k' => $totals->total_100k ?? 0, 'kertas_50k' => $totals->total_50k ?? 0,
                'kertas_20k' => $totals->total_20k ?? 0, 'kertas_10k' => $totals->total_10k ?? 0,
                'kertas_5k' => $totals->total_5k ?? 0, 'kertas_2k' => $totals->total_2k ?? 0,
                'kertas_1k' => $totals->total_1k ?? 0, 'logam_1k' => $totals->total_l1k ?? 0,
                'logam_500' => $totals->total_l500 ?? 0, 'logam_200' => $totals->total_l200 ?? 0,
                'logam_100' => $totals->total_l100 ?? 0, 'total_nominal' => $totals->grand_total ?? 0,
                'total_setoran' => $totalPerJenis['Setoran'] ?? 0,
                'total_penarikan' => $totalPerJenis['Penarikan'] ?? 0,
            ]);
        }
    }

    public function bank(): BelongsTo { return $this->belongsTo(Bank::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }

    public function details(): HasMany { return $this->hasMany(EkuTransactionDetail::class); }

    public function scopeForBank(Builder $query, ?int $bankId): Builder
    {
        return $bankId ? $query->where('bank_id', $bankId) : $query;
    }

    public function isEditableByBankOwner(): bool
    {
        return in_array($this->status, [self::STATUS_MENUNGGU, self::STATUS_REVISI], true);
    }

    public function isLocked(): bool
    {
        return $this->status === self::STATUS_DISETUJUI;
    }
}
