<?php

namespace App\Filament\Resources\EkuTransactions\Tables;

use App\Models\Bank;
use App\Models\EkuTransaction;
use App\Support\CurrentUser;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
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

                TextColumn::make('total_nominal')
                    ->label('Total Nominal (Rp)')
                    ->numeric(
                        decimalPlaces: 0,
                        decimalSeparator: ',',
                        thousandsSeparator: '.',
                    )
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
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
