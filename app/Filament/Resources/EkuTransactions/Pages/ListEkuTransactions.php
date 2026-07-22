<?php

namespace App\Filament\Resources\EkuTransactions\Pages;

use App\Filament\Resources\EkuTransactions\EkuTransactionResource;
use App\Filament\Resources\EkuTransactions\Widgets\TemplateKerjaWidget;
use App\Support\CurrentUser;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;
use Filament\Support\Enums\Width;

class ListEkuTransactions extends ListRecords
{
    protected static string $resource = EkuTransactionResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            TemplateKerjaWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Buat Pengajuan Baru')
                ->modalHeading('Buat Pengajuan EKU ')
                ->modalWidth(Width::TwoExtraLarge)
                ->mutateFormDataUsing(function (array $data): array {
                    $user = CurrentUser::get();

                    if ($user?->isUserPerbankan()) {
                        $data['bank_id'] = $user->bank_id;
                    }

                    $data['user_id'] = Auth::id();

                    return $data;
                }),
        ];
    }
}
