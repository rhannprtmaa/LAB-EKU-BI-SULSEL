<?php

namespace App\Filament\Resources\EkuTransactions\Pages;

use App\Filament\Resources\EkuTransactions\EkuTransactionResource;
use App\Filament\Resources\EkuTransactions\Widgets\TemplateKerjaWidget;
use App\Models\EkuDeadline;
use App\Support\CurrentUser;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class ListEkuTransactions extends ListRecords
{
    protected static string $resource = EkuTransactionResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            TemplateKerjaWidget::class,
        ];
    }

    /**
     * Batas Pengajuan EKU (User Bank) sudah lewat?
     * Hanya relevan untuk User Perbankan -- Admin/User BI tidak dibatasi.
     */
    protected function batasPengajuanSudahLewat(): bool
    {
        $user = CurrentUser::get();

        return (bool) ($user?->isUserPerbankan() && EkuDeadline::isTertutup());
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Buat Pengajuan Baru')
                ->modalHeading('Buat Pengajuan EKU')
                ->modalWidth(Width::TwoExtraLarge)
                ->visible(fn (): bool => EkuTransactionResource::canCreate() && ! $this->batasPengajuanSudahLewat())
                ->mutateFormDataUsing(function (array $data): array {
                    $user = CurrentUser::get();

                    if ($user?->isUserPerbankan()) {
                        $data['bank_id'] = $user->bank_id;
                    }

                    $data['user_id'] = Auth::id();

                    return $data;
                }),
            Action::make('batas_waktu_habis')
                ->label('Buat Pengajuan Baru')
                ->icon('heroicon-o-lock-closed')
                ->color('danger')
                ->visible(fn (): bool => EkuTransactionResource::canCreate() && $this->batasPengajuanSudahLewat())
                ->modalHeading('Batas Waktu Pengajuan EKU Telah Berakhir')
                ->modalIcon('heroicon-o-exclamation-triangle')
                ->modalIconColor('danger')
                ->modalDescription(function (): HtmlString {
                    $deadline = EkuDeadline::current();
                    $tanggal = $deadline?->batas_waktu?->locale('id')->translatedFormat('d F Y');

                    return new HtmlString(
                        ($tanggal ? "Batas waktu pengajuan EKU telah berakhir sejak <strong>{$tanggal}</strong>. " : 'Batas waktu pengajuan EKU telah berakhir. ')
                        .'Sistem tidak dapat menerima input file EKU baru untuk saat ini.'
                        .'<br><br>Apabila Bank Anda tetap memerlukan waktu tambahan, silakan mengajukan '
                        .'<strong>surat resmi ke Bank Indonesia</strong> untuk permohonan perpanjangan masa waktu pengajuan EKU.'
                    );
                })
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Tutup'),
        ];
    }
}
