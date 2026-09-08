<?php

namespace App\Filament\Resources\EkuTransactions\Tables;

use App\Filament\Resources\EkuTransactions\EkuTransactionResource;
use App\Models\Bank;
use App\Models\EkuDeadline;
use App\Models\EkuTransaction;
use App\Support\CurrentUser;
use App\Support\Rupiah;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
                    ->formatStateUsing(fn ($state) => is_null($state) ? '-' : Rupiah::format((float) $state))
                    ->sortable(),

                TextColumn::make('total_penarikan')
                    ->label('Total Penarikan')
                    ->formatStateUsing(fn ($state) => is_null($state) ? '-' : Rupiah::format((float) $state))
                    ->sortable(),

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

                TextColumn::make('approver.name')
                    ->label('Direview oleh')
                    ->placeholder('-'),

                TextColumn::make('created_at')
                    ->label('Tanggal Pengajuan')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('periode')
    ->label('Tahun Periode')
    ->options(function () {
        return EkuTransaction::query()
            ->whereNotNull('periode')
            ->distinct()
            ->orderBy('periode', 'desc')
            ->pluck('periode', 'periode')
            ->toArray();
    })
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'],
                            fn (Builder $query, $tahun) => $query->whereYear('created_at', $tahun)
                        );
                    }),

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

                EditAction::make()
                    ->label('Edit')
                    ->modalHeading('Edit Pengajuan EKU')
                    ->modalWidth(Width::TwoExtraLarge)
                    ->visible(fn ($record) => EkuTransactionResource::canEdit($record))
                    ->before(function (EditAction $action, EkuTransaction $record) {
                        $currentUser = CurrentUser::get();

                        if ($currentUser?->isUserPerbankan() && EkuDeadline::isTertutup()) {
                            $deadline = EkuDeadline::current();
                            $tanggal = $deadline?->batas_waktu?->locale('id')->translatedFormat('d F Y');

                            Notification::make()
                                ->danger()
                                ->persistent()
                                ->title('Batas Waktu Pengeditan Telah Berakhir')
                                ->body(
                                    ($tanggal ? "Batas waktu pengajuan EKU telah berakhir sejak {$tanggal}. " : 'Batas waktu pengajuan EKU telah berakhir. ')
                                    .'Silakan bersurat resmi ke Bank Indonesia untuk permohonan perpanjangan masa waktu pengajuan.'
                                )
                                ->send();

                            $action->halt();
                        }
                    }),

                DeleteAction::make()
                    ->visible(fn ($record) => EkuTransactionResource::canDelete($record)),
            ]);
    }
}