<?php

namespace App\Filament\Resources\EkuTransactions\Tables;

use App\Filament\Resources\EkuTransactions\EkuTransactionResource;
use App\Models\Bank;
use App\Models\EkuTransaction;
use App\Support\CurrentUser;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
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

                // KOLOM BARU: Total Realisasi Setoran
                TextColumn::make('total_realisasi_setoran')
                    ->label('Realisasi Setoran')
                    ->numeric(0, ',', '.')
                    ->placeholder('-')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // KOLOM BARU: Total Realisasi Penarikan
                TextColumn::make('total_realisasi_penarikan')
                    ->label('Realisasi Penarikan')
                    ->numeric(0, ',', '.')
                    ->placeholder('-')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // KOLOM BARU: Deviasi Setoran
                TextColumn::make('deviasi_setoran')
                    ->label('Deviasi Setoran')
                    ->numeric(0, ',', '.')
                    ->placeholder('-')
                    ->color(fn ($state) => $state < 0 ? 'danger' : ($state > 0 ? 'success' : 'gray'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // KOLOM BARU: Deviasi Penarikan
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

                // ACTION BARU: Input Realisasi EKU oleh Pihak BI
                Action::make('uploadRealisasi')
                    ->label('Input Realisasi')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('success')
                    ->visible($isInternalBi)
                    ->form([
                        FileUpload::make('file_realisasi_setoran')
                            ->label('Upload File Excel Realisasi Setoran')
                            ->disk('public')
                            ->directory('realisasi-eku/setoran')
                            ->acceptedFileTypes([
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            ])
                            ->required(),

                        FileUpload::make('file_realisasi_penarikan')
                            ->label('Upload File Excel Realisasi Penarikan')
                            ->disk('public')
                            ->directory('realisasi-eku/penarikan')
                            ->acceptedFileTypes([
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            ])
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'file_realisasi_setoran' => $data['file_realisasi_setoran'],
                            'file_realisasi_penarikan' => $data['file_realisasi_penarikan'],
                            'realisasi_uploaded_at' => now(),
                        ]);

                        // Panggil method hitungDeviasi jika kodenya sudah ada di Model
                        if (method_exists($record, 'hitungDeviasi')) {
                            $record->hitungDeviasi();
                        }

                        Notification::make()
                            ->title('File Realisasi EKU Berhasil Diunggah!')
                            ->success()
                            ->send();
                    }),

                EditAction::make()
                    ->label('Edit')
                    ->modalHeading('Edit Pengajuan EKU')
                    ->modalWidth(Width::TwoExtraLarge)
                    ->visible(fn ($record) => EkuTransactionResource::canEdit($record)),

                DeleteAction::make()
                    ->visible(fn ($record) => EkuTransactionResource::canDelete($record)),
            ]);
    }
}
