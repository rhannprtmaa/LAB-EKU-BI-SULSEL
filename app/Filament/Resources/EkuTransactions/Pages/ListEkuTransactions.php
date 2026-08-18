<?php

namespace App\Filament\Resources\EkuTransactions\Pages;

use App\Filament\Resources\EkuTransactions\EkuTransactionResource;
use App\Filament\Resources\EkuTransactions\Widgets\TemplateKerjaWidget;
use App\Models\EkuTransaction;
use App\Support\CurrentUser;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;
use Filament\Support\Exceptions\Halt;
use Illuminate\Support\Facades\Auth;

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
                ->modalHeading('Buat Pengajuan EKU')
                ->modalWidth(Width::TwoExtraLarge)
                ->visible(fn (): bool => EkuTransactionResource::canCreate())
                ->mutateFormDataUsing(function (array $data): array {
                    $user = CurrentUser::get();

                    if ($user?->isUserPerbankan()) {
                        $data['bank_id'] = $user->bank_id;
                    }

                    $data['user_id'] = Auth::id();

                    return $data;
                })
                ->using(function (array $data): EkuTransaction {
                    // Cari pengajuan EKU yang SUDAH ADA untuk bank + periode
                    // yang sama -- kalau ketemu, TIMPA (update) data lamanya,
                    // JANGAN bikin baris baru. Ini mencegah satu bank punya
                    // banyak EkuTransaction untuk periode yang sama, yang
                    // sebelumnya bikin realisasi/deviasi nyasar/numpuk ke
                    // transaksi yang salah.
                    $existing = EkuTransaction::query()
                        ->where('bank_id', $data['bank_id'])
                        ->where('periode', $data['periode'])
                        ->first();

                    if (! $existing) {
                        return EkuTransaction::create($data);
                    }

                    if ($existing->status === EkuTransaction::STATUS_DISETUJUI) {
                        Notification::make()
                            ->title('Periode ' . $data['periode'] . ' sudah Disetujui dan terkunci')
                            ->body('Tidak bisa diajukan ulang. Kalau perlu revisi, hubungi Bank Indonesia.')
                            ->danger()
                            ->persistent()
                            ->send();

                        throw new Halt();
                    }

                    // Timpa file & data lama dengan yang baru diupload.
                    // Event model (saved -> reprocessExcelFiles) tetap
                    // otomatis jalan karena ini update() Eloquent biasa.
                    $existing->update($data);

                    Notification::make()
                        ->title('Pengajuan EKU periode ' . $data['periode'] . ' berhasil diperbarui')
                        ->body('Sudah ada pengajuan untuk periode ini sebelumnya, jadi datanya ditimpa dengan file yang baru diupload (bukan membuat pengajuan baru).')
                        ->success()
                        ->send();

                    return $existing->fresh();
                }),
        ];
    }
}
