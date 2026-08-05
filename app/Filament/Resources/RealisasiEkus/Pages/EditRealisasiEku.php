<?php

namespace App\Filament\Resources\RealisasiEkus\Pages;

use App\Filament\Resources\RealisasiEkus\RealisasiEkuResource;
use App\Models\EkuTransactionRealisasi; // Pastikan model history dipanggil
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditRealisasiEku extends EditRecord
{
    protected static string $resource = RealisasiEkuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // 1. Ambil data file (dan deviasi jika perlu) dari array form data
        $fileRealisasi = $data['file_realisasi'] ?? null;

        // 2. Hapus field dari array $data agar Filament tidak mencoba
        // menyimpannya ke tabel lama (eku_transactions) yang menyebabkan error
        unset($data['file_realisasi']);

        // 3. Lakukan update data utama (misal update status transaksi menjadi selesai)
        $record->update($data);

        // 4. Simpan file ke tabel history baru (EkuTransactionRealisasi)
        if ($fileRealisasi) {
            EkuTransactionRealisasi::create([
                'eku_transaction_id' => $record->id,
                'file_realisasi'     => $fileRealisasi,
                // Sesuaikan dengan kolom lain di migrasi 2026_08_05_002909_create_eku_transaction_realisasis_table.php
            ]);
        }

        return $record;
    }
}
