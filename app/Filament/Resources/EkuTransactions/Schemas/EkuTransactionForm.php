<?php

namespace App\Filament\Resources\EkuTransactions\Schemas;

use App\Models\EkuDeadline;
use App\Models\EkuTransaction;
use App\Support\CurrentUser;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class EkuTransactionForm
{
    protected static function namaFileAsli(): \Closure
    {
        return fn ($file) => date('YmdHis') . '_' . $file->getClientOriginalName();
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('bank_name')
                    ->label('Nama Bank')
                    ->default(fn ($record) => $record?->bank?->name ?? Auth::user()->bank?->name ?? '-')
                    ->disabled()
                    ->dehydrated(false),

                Select::make('periode')
                    ->label('Periode (Tahun)')
                    ->live()
                    ->options(fn () => collect([date('Y'), date('Y') + 1, date('Y') + 2])
                        ->mapWithKeys(function ($tahun) {
                            $label = EkuDeadline::isTertutup((string) $tahun)
                                ? "{$tahun} (Ditutup)"
                                : (string) $tahun;

                            return [(string) $tahun => $label];
                        })
                        ->all())
                    ->disableOptionWhen(fn (string $value): bool => CurrentUser::get()?->isUserPerbankan()
                        && EkuDeadline::isTertutup($value))
                    ->default(date('Y') + 1)
                    ->required()
                    ->rule(function () {
                        return function (string $attribute, $value, \Closure $fail) {
                            if (CurrentUser::get()?->isUserPerbankan() && EkuDeadline::isTertutup($value)) {
                                $fail('Periode ini sudah melewati batas waktu pengajuan EKU yang ditentukan oleh BI.');
                            }
                        };
                    }),

                // PERBAIKAN: Tambahkan ->dehydrated(false) agar tidak dimasukkan ke query SQL INSERT
                Placeholder::make('batasan_periode_info')
                    ->label('Batasan Periode')
                    ->columnSpanFull()
                    ->dehydrated(false)
                    ->content(function (Get $get) {
                        $deadline = EkuDeadline::untukPeriode($get('periode'));

                        if (! $deadline || ! $deadline->batas_waktu) {
                            return new HtmlString('<span class="text-gray-400">Belum ditentukan oleh BI untuk periode ini.</span>');
                        }

                        $tanggal = $deadline->batas_waktu->locale('id')->translatedFormat('d F Y');

                        if ($deadline->isSudahLewat()) {
                            return new HtmlString("<span class=\"text-danger-600 font-medium\">Batas Pengajuan s.d {$tanggal} — periode ini sudah ditutup.</span>");
                        }

                        return new HtmlString("Batas Pengajuan s.d <span class=\"font-medium\">{$tanggal}</span>");
                    }),

                FileUpload::make('file_setoran')
                    ->label('File Excel Setoran')
                    ->disk('public')
                    ->directory('pengajuan-eku/setoran')
                    ->visibility('public')
                    ->getUploadedFileNameForStorageUsing(static::namaFileAsli())
                    ->acceptedFileTypes([
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    ])
                    ->maxSize(5120),

                FileUpload::make('file_penarikan')
                    ->label('File Excel Penarikan')
                    ->disk('public')
                    ->directory('pengajuan-eku/penarikan')
                    ->visibility('public')
                    ->getUploadedFileNameForStorageUsing(static::namaFileAsli())
                    ->acceptedFileTypes([
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    ])
                    ->maxSize(5120),

                FileUpload::make('file_lampiran')
                    ->label('File Lampiran (PDF / Pendukung)')
                    ->disk('public')
                    ->directory('pengajuan-eku/lampiran')
                    ->getUploadedFileNameForStorageUsing(static::namaFileAsli())
                    ->acceptedFileTypes(['application/pdf', 'image/*'])
                    ->maxSize(10240)
                    ->columnSpanFull(),

                Section::make('Feedback dari BI')
                    ->columnSpanFull()
                    ->visible(fn ($record) => filled($record) && filled($record->catatan))
                    ->columns(2)
                    ->schema([
                        TextInput::make('status_display')
                            ->label('Status Saat Ini')
                            ->default(fn ($record) => $record ? (EkuTransaction::statusOptions()[$record->status] ?? $record->status) : '-')
                            ->disabled()
                            ->dehydrated(false),

                        Textarea::make('catatan')
                            ->label('Catatan dari User BI')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Belum ada catatan')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                Hidden::make('bank_id')->default(fn () => Auth::user()?->bank_id),
                Hidden::make('user_id')->default(fn () => Auth::id()),
            ]);
    }
}
