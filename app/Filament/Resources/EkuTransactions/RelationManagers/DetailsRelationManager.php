<?php

namespace App\Filament\Resources\EkuTransactions\RelationManagers;

use App\Models\EkuTransaction;
use App\Models\EkuTransactionDetail;
use App\Support\CurrentUser;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Table;

class DetailsRelationManager extends RelationManager
{
    protected static string $relationship = 'details';

    protected static ?string $title = 'Rincian Proyeksi EKU Bulanan';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        $bisaEdit = CurrentUser::get()?->isUserBi() ?? false;

        $kolomPecahan = [
            'Rp 100.000' => 'kertas_100k',
            'Rp 50.000' => 'kertas_50k',
            'Rp 20.000' => 'kertas_20k',
            'Rp 10.000' => 'kertas_10k',
            'Rp 5.000' => 'kertas_5k',
            'Rp 2.000' => 'kertas_2k',
            'Rp 1.000 (K)' => 'kertas_1k',
            'Rp 1.000 (L)' => 'logam_1k',
            'Rp 500' => 'logam_500',
            'Rp 200' => 'logam_200',
            'Rp 100' => 'logam_100',
        ];

        $kolom = [
            TextColumn::make('bulan')->label('Bulan')->searchable(),
            TextColumn::make('jenis_file')->label('Jenis')->badge()
                ->color(fn (string $state): string => match ($state) {
                    'Setoran' => 'warning',
                    'Penarikan' => 'info',
                    default => 'gray',
                }),
        ];

        foreach ($kolomPecahan as $label => $namaKolom) {
            $kolom[] = $bisaEdit
                ? $this->buildEditableColumn($namaKolom, $label)
                : TextColumn::make($namaKolom)->label($label)->numeric(0, ',', '.');
        }

        $kolom[] = TextColumn::make('subtotal')
            ->label('Subtotal')
            ->numeric(0, ',', '.')
            ->bold();

        return $table
            ->recordTitleAttribute('bulan')
            ->columns($kolom)
            ->filters([])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }

    protected function buildEditableColumn(string $namaKolom, string $label): TextInputColumn
    {
        return TextInputColumn::make($namaKolom)
            ->label($label)
            ->type('number')
            ->rules(['numeric', 'min:0'])
            ->afterStateUpdated(function (EkuTransactionDetail $record): void {
                $record->recalculateSubtotal();

                EkuTransaction::recalculateTotals($record->eku_transaction_id);

                EkuTransaction::whereKey($record->eku_transaction_id)->update([
                    'is_edited_by_bi' => true,
                ]);
            });
    }
}
