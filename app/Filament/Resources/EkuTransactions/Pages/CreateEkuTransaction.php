<?php

namespace App\Filament\Resources\EkuTransactions\Pages;

use App\Filament\Resources\EkuTransactions\EkuTransactionResource;
use App\Support\CurrentUser;
use Filament\Resources\Pages\CreateRecord;

class CreateEkuTransaction extends CreateRecord
{
    protected static string $resource = EkuTransactionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = CurrentUser::get();

        if ($user?->isUserPerbankan()) {
            $data['bank_id'] = $user->bank_id;
        }

        $data['user_id'] = $user?->id;

        return $data;
    }
}
