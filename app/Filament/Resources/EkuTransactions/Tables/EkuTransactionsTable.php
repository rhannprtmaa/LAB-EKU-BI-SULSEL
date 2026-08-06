<?php

namespace App\Filament\Resources\EkuTransactions\Tables;

use App\Filament\Resources\EkuTransactions\EkuTransactionResource;
use App\Models\Bank;
use App\Models\EkuDeadline;
use App\Models\EkuTransaction;
use App\Support\CurrentUser;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class EkuTransactionsTable
{
    public static function configure(Table $table): Table
    {
        $user = CurrentUser::get();
        $isInternalBi = (bool) ($user?->isAdminBi() || $user?->isUserBi());

        return $table
            ->columns([
                TextColumn::make('bank.name')
                    ->label('Nama Bank')
                    ->searchable()
                    ->sortable()
                    ->visible($isInternalBi),

                TextColumn::make('user.name')
                    ->label('Petugas Pembuat')
                    ->searchable(),

                TextColumn::make('periode')
                    ->label('Periode')
                    ->sortable(),

                TextColumn::make('total_setoran')
                    ->label('Total Setoran')
                    ->numeric(
                        decimalPlaces: 0,
                        decimalSeparator: ',',
                        thousandsSeparator: '.',
                    )
                    ->sortable(),

                TextColumn::make('total_penarikan')
                    ->label('Total Penarikan')
                    ->numeric(
                        decimalPlaces: 0,
                        decimalSeparator: ',',
                        thousandsSeparator: '.',
                    )
                    ->sortable(),

                TextColumn::make('total_realisasi_setoran')
                    ->label('Realisasi Setoran')
                    ->numeric(0, ',', '.')
                    ->placeholder('-')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('total_realisasi_penarikan')
                    ->label('Realisasi Penarikan')
                    ->numeric(0, ',', '.')
                    ->placeholder('-')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('deviasi_setoran')
                    ->label('Deviasi Setoran')
                    ->numeric(0, ',', '.')
                    ->placeholder('-')
                    ->color(fn ($state) => $state < 0 ? 'danger' : ($state > 0 ? 'success' : 'gray'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('deviasi_penarikan')
                    ->label('Deviasi Penarikan')
                    ->numeric(0, ',', '.')
                    ->placeholder('-')
                    ->color(fn ($state) => $state < 0 ? 'danger' : ($state > 0 ? 'success' : 'gray'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        EkuTransaction::STATUS_MENUNGGU => 'warning',
                        EkuTransaction::STATUS_DISETUJUI => 'success',
                        EkuTransaction::STATUS_REVISI => 'danger',
                        EkuTransaction::STATUS_DITOLAK => 'danger',
                        default => 'gray',
                    }),

                IconColumn::make('is_edited_by_bi')
                    ->label('Direvisi BI')
                    ->boolean()
                    ->trueIcon('heroicon-o-pencil-square')
                    ->falseIcon('heroicon-o-minus')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('approver.name')
                    ->label('Direview oleh')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Tanggal Pengajuan')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(EkuTransaction::statusOptions()),

                SelectFilter::make('bank_id')
                    ->label('Bank')
                    ->visible($isInternalBi)
                    ->options(fn () => Bank::query()->pluck('name', 'id')->toArray())
                    ->searchable(),
            ])
            ->actions([
                ViewAction::make()
                    ->label('Detail'),

                // Tombol tambahan untuk menyesuaikan batasan bank secara otomatis (Khusus Admin BI)
                Action::make('sesuaikan_batasan')
                    ->label('Sesuaikan Batasan')
                    ->icon('heroicon-o-scale')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Terapkan Batasan Bank')
                    ->modalDescription('Apakah Anda ingin mencocokkan nominal transaksi ini dengan batas maksimal yang diizinkan untuk bank tersebut?')
                    ->visible(fn (EkuTransaction $record) => $user?->isAdminBi())
                    ->action(function (EkuTransaction $record) {
                        $disesuaikan = $record->terapkanBatasanBank();

                        if ($disesuaikan) {
                            Notification::make()
                                ->title('Berhasil Menyesuaikan Batasan')
                                ->body('Nominal transaksi telah dipangkas sesuai dengan batasan maksimal bank.')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Tidak Ada Perubahan')
                                ->body('Nominal transaksi ini masih berada di bawah atau sama dengan batas maksimal.')
                                ->info()
                                ->send();
                        }
                    }),

                EditAction::make()
                    ->label('Edit')
                    ->modalHeading('Edit Pengajuan EKU')
                    ->modalWidth(Width::TwoExtraLarge)
                    ->visible(fn ($record) => EkuTransactionResource::canEdit($record))
                    ->before(function (EditAction $action, EkuTransaction $record) {
                        $currentUser = CurrentUser::get();

                        if ($currentUser?->isUserPerbankan()) {
                            $deadline = EkuDeadline::where('periode', $record->periode)->first();

                            if ($deadline && now()->isAfter($deadline->batas_waktu)) {
                                Notification::make()
                                    ->danger()
                                    ->title('Akses Ditolak!')
                                    ->body("Waktu pengeditan / revisi EKU untuk periode {$record->periode} telah berakhir pada " . Carbon::parse($deadline->batas_waktu)->translatedFormat('d F Y H:i') . ".")
                                    ->send();

                                $action->halt();
                            }
                        }
                    }),

                DeleteAction::make()
                    ->visible(fn ($record) => EkuTransactionResource::canDelete($record)),
            ]);
    }
}
