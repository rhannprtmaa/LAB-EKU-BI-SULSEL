<?php

namespace App\Filament\Resources\EkuTransactions\Pages; // <-- FIX: Menggunakan nama folder yang benar (EkuTransactions)

use App\Filament\Resources\EkuTransactions\EkuTransactionResource; // <-- FIX: Import Resource yang benar
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEkuTransactions extends ListRecords
{
    protected static string $resource = EkuTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Cukup gunakan ini, karena rute Create sudah dihapus,
            // Filament otomatis menyulap tombol ini jadi Pop-up Modal!
            CreateAction::make(),
        ];
    }
}
