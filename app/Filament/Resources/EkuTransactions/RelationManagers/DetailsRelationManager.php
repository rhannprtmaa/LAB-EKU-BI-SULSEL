<?php

namespace App\Filament\Resources\EkuTransactions\RelationManagers;

use App\Models\EkuTransaction;
use App\Models\EkuTransactionDetail;
use App\Support\CurrentUser;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Grouping\Group;
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

        $urutanBulan = [
            'Januari' => 1, 'Februari' => 2, 'Maret' => 3, 'April' => 4,
            'Mei' => 5, 'Juni' => 6, 'Juli' => 7, 'Agustus' => 8,
            'September' => 9, 'Oktober' => 10, 'November' => 11, 'Desember' => 12,
        ];

        $kolom = [
            TextColumn::make('bulan')
                ->label('Bulan')
                ->searchable()
                ->summarize(
                    \Filament\Tables\Columns\Summarizers\Summarizer::make()
                        ->label('Grand Total')
                        ->using(fn () => 'Grand Total')
                ),
        ];

        foreach ($kolomPecahan as $label => $namaKolom) {
            $kolom[] = ($bisaEdit
                ? $this->buildEditableColumn($namaKolom, $label)
                : TextColumn::make($namaKolom)->label($label)->numeric(0, ',', '.')
            )->summarize(
                Sum::make()->label('')->numeric(decimalPlaces: 0, decimalSeparator: ',', thousandsSeparator: '.')
            );
        }

        $kolom[] = TextColumn::make('subtotal')
            ->label('Subtotal')
            ->numeric(0, ',', '.')
            ->weight(FontWeight::Bold)
            ->summarize(
                Sum::make()->label('')->numeric(decimalPlaces: 0, decimalSeparator: ',', thousandsSeparator: '.')
            );

        return $table
            ->recordTitleAttribute('bulan')
            // --- DITAMBAHKAN DI SINI ---
            ->paginated([12, 24]) 
            ->defaultPaginationPageOption(12)
            // ---------------------------
            ->columns($kolom)
            ->defaultGroup(
                Group::make('jenis_file')
                    ->label('Jenis')
                    ->collapsible()
            )
            ->modifyQueryUsing(function ($query) use ($urutanBulan) {
                $caseSql = 'CASE bulan ' . collect($urutanBulan)
                    ->map(fn ($angka, $bulan) => "WHEN '{$bulan}' THEN {$angka}")
                    ->implode(' ') . ' END';

                return $query->orderBy('jenis_file')->orderByRaw($caseSql);
            })
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
