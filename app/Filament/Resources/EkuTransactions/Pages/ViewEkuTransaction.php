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
            Action::make('approve')
                ->label('Setujui Pengajuan')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->visible(fn (): bool => (bool) CurrentUser::get()?->isUserBi()
                    && $this->record->status !== EkuTransaction::STATUS_DISETUJUI)
                ->requiresConfirmation()
                ->modalWidth(Width::Medium)
                ->modalDescription('Pengajuan akan ditandai Disetujui dan dikunci. Bank akan menerima notifikasi.')
                ->schema([
                    Textarea::make('catatan')
                        ->label('Catatan untuk Bank (opsional)')
                        ->default(fn () => $this->record->catatan)
                        ->rows(3),
                ])
                ->action(function (array $data): void {
                    $this->record->update([
                        'status' => EkuTransaction::STATUS_DISETUJUI,
                        'approved_by' => Auth::id(),
                        'approved_at' => now(),
                        'catatan' => $data['catatan'] ?? $this->record->catatan,
                    ]);

                    Notification::make()->title('Pengajuan berhasil disetujui')->success()->send();
                }),

            Action::make('requestRevision')
                ->label('Kembalikan untuk Revisi')
                ->color('warning')
                ->icon('heroicon-o-arrow-uturn-left')
                ->visible(fn (): bool => (bool) CurrentUser::get()?->isUserBi()
                    && $this->record->status !== EkuTransaction::STATUS_DISETUJUI)
                ->requiresConfirmation()
                ->modalWidth(Width::Medium)
                ->modalDescription('Bank akan diminta memperbaiki dan mengunggah ulang data sesuai catatan.')
                ->schema([
                    Textarea::make('catatan')->label('Catatan Perbaikan (wajib diisi)')->required()->rows(3),
                ])
                ->action(function (array $data): void {
                    $this->record->update([
                        'status' => EkuTransaction::STATUS_REVISI,
                        'approved_by' => Auth::id(),
                        'approved_at' => now(),
                        'catatan' => $data['catatan'],
                    ]);

                    Notification::make()->title('Pengajuan dikembalikan untuk revisi')->warning()->send();
                }),
        ];
    }
}
