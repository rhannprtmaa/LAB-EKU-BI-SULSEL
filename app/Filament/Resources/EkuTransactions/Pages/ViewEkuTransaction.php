<?php

namespace App\Filament\Resources\EkuTransactions\Pages;

use App\Filament\Resources\EkuTransactions\EkuTransactionResource;
use Filament\Resources\Pages\ViewRecord;

class ViewEkuTransaction extends ViewRecord
{
    protected static string $resource = EkuTransactionResource::class;

    protected function getHeaderActions(): array
    {
        // Sengaja dikosongkan: halaman ini murni untuk monitoring (Admin BI),
        // tidak ada aksi ubah data di sini.
        return [];
    }
}
