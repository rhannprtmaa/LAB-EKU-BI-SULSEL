<?php

namespace App\Filament\Resources\EkuTransactions\Pages;

use App\Filament\Resources\EkuTransactions\EkuTransactionResource;
use App\Filament\Resources\EkuTransactions\Widgets\TemplateKerjaWidget;
use App\Models\EkuDeadline;
use App\Support\CurrentUser;
use Carbon\Carbon;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
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
                ->visible(fn (): bool => EkuTransactionResource::canCreate())

                // --- Validasi Deadline Pengajuan ---
                ->before(function (CreateAction $action, array $data) {
                    $user = CurrentUser::get();

                    // Pengecekan deadline HANYA berlaku untuk User Perbankan
                    if ($user?->isUserPerbankan()) {

                        $periode = $data['periode'] ?? date('Y');

                        // Mencari data deadline untuk periode yang dipilih
                        $deadline = EkuDeadline::where('periode', $periode)->first();

                        // Menggunakan kolom 'batas_waktu' sesuai database Anda
                        if ($deadline && now()->isAfter($deadline->batas_waktu)) {

                            Notification::make()
                                ->danger()
                                ->title('Batas Waktu Pengajuan Habis!')
                                ->body("Waktu pengajuan EKU untuk periode {$periode} telah ditutup oleh Admin BI sejak " . Carbon::parse($deadline->batas_waktu)->translatedFormat('d F Y H:i') . ".")
                                ->send();

                            // Membatalkan proses pop-up form terbuka/tersimpan
                            $action->halt();
                        }
                    }
                })

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
