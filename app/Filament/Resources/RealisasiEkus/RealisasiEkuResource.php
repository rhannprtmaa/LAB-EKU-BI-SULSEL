<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RealisasiEkuResource\Pages;
use App\Models\EkuTransaction;
use App\Support\CurrentUser;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RealisasiEkuResource extends Resource
{
    protected static ?string $model = EkuTransaction::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Realisasi & Deviasi EKU';
    protected static ?string $modelLabel = 'Realisasi EKU';
    protected static ?int $navigationSort = 2;

    // Mengatur agar hanya User BI dan Admin BI yang bisa melihat menu ini di sidebar
    public static function canViewAny(): bool
    {
        $user = CurrentUser::get();
        return (bool) ($user?->isAdminBi() || $user?->isUserBi());
    }

    public static function table(Table $table): Table
    {
        $user = CurrentUser::get();
        // Hanya User BI murni yang bisa menginput realisasi, Admin BI hanya monitoring
        $isUserBiOnly = (bool) $user?->isUserBi();

        return $table
            ->query(
                EkuTransaction::query()->where('status', EkuTransaction::STATUS_DISETUJUI)
            )
            ->columns([
                TextColumn::make('bank.name')
                    ->label('Nama Bank')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('periode')
                    ->label('Periode')
                    ->sortable(),

                TextColumn::make('total_setoran')
                    ->label('Pengajuan Setoran')
                    ->numeric(0, ',', '.'),

                TextColumn::make('total_realisasi_setoran')
                    ->label('Realisasi Setoran')
                    ->numeric(0, ',', '.')
                    ->placeholder('Belum ada'),

                TextColumn::make('deviasi_setoran')
                    ->label('Deviasi Setoran')
                    ->numeric(0, ',', '.')
                    ->placeholder('-')
                    ->color(fn ($state) => $state < 0 ? 'danger' : ($state > 0 ? 'success' : 'gray')),

                TextColumn::make('total_penarikan')
                    ->label('Pengajuan Penarikan')
                    ->numeric(0, ',', '.'),

                TextColumn::make('total_realisasi_penarikan')
                    ->label('Realisasi Penarikan')
                    ->numeric(0, ',', '.')
                    ->placeholder('Belum ada'),

                TextColumn::make('deviasi_penarikan')
                    ->label('Deviasi Penarikan')
                    ->numeric(0, ',', '.')
                    ->placeholder('-')
                    ->color(fn ($state) => $state < 0 ? 'danger' : ($state > 0 ? 'success' : 'gray')),

                TextColumn::make('realisasi_uploaded_at')
                    ->label('Tgl Upload Realisasi')
                    ->dateTime('d M Y H:i')
                    ->placeholder('Belum Diinput'),
            ])
            ->actions([
                ViewAction::make()->label('Detail'),

                // Tombol Input Realisasi HANYA MUNCUL untuk User BI (Admin BI tidak ada tombol ini)
                Action::make('uploadRealisasi')
                    ->label('Input Realisasi')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('success')
                    ->visible($isUserBiOnly)
                    ->form([
                        FileUpload::make('file_realisasi_setoran')
                            ->label('File Excel Realisasi Setoran')
                            ->disk('public')
                            ->directory('realisasi-eku/setoran')
                            ->acceptedFileTypes([
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            ])
                            ->required(),

                        FileUpload::make('file_realisasi_penarikan')
                            ->label('File Excel Realisasi Penarikan')
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
                        ]);

                        // Proses baca excel realisasi & hitung deviasi
                        $record->processRealisasiExcel(
                            $data['file_realisasi_setoran'],
                            $data['file_realisasi_penarikan']
                        );

                        Notification::make()
                            ->title('File Realisasi Berhasil Diproses & Deviasi Terhitung!')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRealisasiEkus::route('/'),
        ];
    }

}
