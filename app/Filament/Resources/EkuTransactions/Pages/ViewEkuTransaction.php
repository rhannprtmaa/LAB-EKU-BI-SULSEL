<?php

namespace App\Filament\Resources\EkuTransactions\Pages;

use App\Filament\Resources\EkuTransactions\EkuTransactionResource;
use App\Models\EkuTransaction;
use App\Support\CurrentUser;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;
use Filament\Support\Enums\Width;

class ViewEkuTransaction extends ViewRecord
{
    protected static string $resource = EkuTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [

            // ========================================================
            // SESUAIKAN BATASAN
            // ========================================================

            Action::make('sesuaikan_batasan')
                ->label('Sesuaikan Batasan')
                ->color('warning')
                ->icon('heroicon-o-arrows-right-left')
                ->visible(
                    fn (): bool =>
                    (bool) CurrentUser::get()?->isUserBi()
                    && $this->record->status !== EkuTransaction::STATUS_DISETUJUI
                )
                ->requiresConfirmation()
                ->modalWidth(Width::Medium)
                ->modalHeading('Sesuaikan Batasan EKU')
                ->modalDescription(
                    'Tindakan ini akan menyesuaikan nilai proyeksi pengajuan dengan batasan EKU yang telah ditetapkan untuk bank ini. Nilai hanya akan berubah setelah Anda menekan tombol ini.'
                )
                ->action(function () {

                    /*
                     * HANYA tombol ini yang memanggil
                     * terapkanBatasanBank().
                     */
                    $disesuaikan = $this->record->terapkanBatasanBank();

                    if (! $disesuaikan) {

                        Notification::make()
                            ->title('Tidak Ada Penyesuaian')
                            ->body(
                                'Nilai pengajuan tidak melebihi batasan yang ditetapkan, atau belum ada batasan yang aktif.'
                            )
                            ->info()
                            ->send();
                    }

                    /*
                     * Refresh halaman supaya:
                     * - rincian proyeksi berubah
                     * - total berubah
                     * - file hasil penyesuaian tersedia
                     */
                    return redirect(request()->header('Referer'));
                }),

            // ========================================================
            // SETUJUI PENGAJUAN
            // ========================================================

            Action::make('approve')
                ->label('Setujui Pengajuan')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->visible(
                    fn (): bool =>
                    (bool) CurrentUser::get()?->isUserBi()
                    && $this->record->status !== EkuTransaction::STATUS_DISETUJUI
                )
                ->requiresConfirmation()
                ->modalWidth(Width::Medium)
                ->modalHeading('Setujui Pengajuan EKU')
                ->modalDescription(
                    'Pengajuan akan ditandai Disetujui dan dikunci. Nilai proyeksi TIDAK akan disesuaikan dengan batasan pada tindakan ini. Jika perlu menyesuaikan batasan, tekan tombol "Sesuaikan Batasan" terlebih dahulu.'
                )
                ->schema([
                    Textarea::make('catatan')
                        ->label('Catatan untuk Bank (opsional)')
                        ->default(
                            fn () =>
                            $this->record->catatan
                        )
                        ->rows(3),
                ])
                ->action(function (array $data): void {

                    $this->record->update([
                        'status' => EkuTransaction::STATUS_DISETUJUI,
                        'approved_by' => Auth::id(),
                        'approved_at' => now(),
                        'catatan' =>
                        $data['catatan']
                        ?? $this->record->catatan,
                    ]);

                    Notification::make()
                        ->title('Pengajuan berhasil disetujui')
                        ->body(
                            'Pengajuan telah disetujui tanpa melakukan penyesuaian batasan.'
                        )
                        ->success()
                        ->send();
                }),

            // ========================================================
            // KEMBALIKAN UNTUK REVISI
            // ========================================================

            Action::make('requestRevision')
                ->label('Kembalikan untuk Revisi')
                ->color('warning')
                ->icon('heroicon-o-arrow-uturn-left')
                ->visible(
                    fn (): bool =>
                    (bool) CurrentUser::get()?->isUserBi()
                    && $this->record->status !== EkuTransaction::STATUS_DISETUJUI
                )
                ->requiresConfirmation()
                ->modalWidth(Width::Medium)
                ->modalHeading('Kembalikan Pengajuan untuk Revisi')
                ->modalDescription(
                    'Bank akan diminta memperbaiki dan mengunggah ulang data sesuai catatan. Nilai proyeksi tidak akan disesuaikan dengan batasan.'
                )
                ->schema([
                    Textarea::make('catatan')
                        ->label('Catatan Perbaikan (wajib diisi)')
                        ->required()
                        ->rows(3),
                ])
                ->action(function (array $data): void {

                    $this->record->update([
                        'status' => EkuTransaction::STATUS_REVISI,
                        'approved_by' => Auth::id(),
                        'approved_at' => now(),
                        'catatan' => $data['catatan'],
                    ]);

                    Notification::make()
                        ->title('Pengajuan dikembalikan untuk revisi')
                        ->warning()
                        ->send();
                }),
        ];
    }
}
