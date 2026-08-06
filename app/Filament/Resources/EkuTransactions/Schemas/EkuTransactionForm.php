<?php

namespace App\Filament\Resources\EkuTransactions\Schemas;

use App\Models\EkuDeadline;
use App\Models\EkuTransaction;
use App\Support\CurrentUser;
use Carbon\Carbon;
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
                    ->required()
                    ->live()
                    // 1. Ekstrak tahun langsung dari tanggal (batas_waktu)
                    ->options(function () {
                        $deadlines = EkuDeadline::whereNotNull('batas_waktu')->get();
                        $options = [];

                        foreach ($deadlines as $d) {
                            $waktu = $d->batas_waktu instanceof Carbon ? $d->batas_waktu : Carbon::parse($d->batas_waktu);
                            $tahun = $waktu->format('Y'); // Ambil tahunnya saja

                            $isExpired = now()->isAfter($waktu);
                            $options[$tahun] = $isExpired ? "{$tahun} (Ditutup)" : $tahun;
                        }

                        // Urutkan dari tahun terbaru
                        krsort($options);
                        return $options;
                    })
                    // 2. Default ke tahun dari deadline terbaru
                    ->default(function () {
                        $latest = EkuDeadline::whereNotNull('batas_waktu')->latest('batas_waktu')->first();
                        if ($latest) {
                            $waktu = $latest->batas_waktu instanceof Carbon ? $latest->batas_waktu : Carbon::parse($latest->batas_waktu);
                            return $waktu->format('Y');
                        }
                        return date('Y');
                    })
                    // 3. Disable opsi jika sudah lewat
                    ->disableOptionWhen(function (string $value): bool {
                        $user = CurrentUser::get();
                        if ($user?->isUserPerbankan()) {
                            // Cari deadline berdasarkan tahun pada batas_waktu
                            $deadline = EkuDeadline::whereYear('batas_waktu', $value)->first();
                            if ($deadline && $deadline->batas_waktu) {
                                $waktu = $deadline->batas_waktu instanceof Carbon ? $deadline->batas_waktu : Carbon::parse($deadline->batas_waktu);
                                return now()->isAfter($waktu);
                            }
                        }
                        return false;
                    })
                    // 4. Validasi saat disubmit
                    ->rule(function () {
                        return function (string $attribute, $value, \Closure $fail) {
                            $user = CurrentUser::get();
                            if ($user?->isUserPerbankan()) {
                                $deadline = EkuDeadline::whereYear('batas_waktu', $value)->first();
                                if ($deadline && $deadline->batas_waktu) {
                                    $waktu = $deadline->batas_waktu instanceof Carbon ? $deadline->batas_waktu : Carbon::parse($deadline->batas_waktu);
                                    if (now()->isAfter($waktu)) {
                                        $fail('Periode ini sudah melewati batas waktu pengajuan EKU yang ditentukan oleh BI.');
                                    }
                                }
                            }
                        };
                    }),

                Placeholder::make('batasan_periode_info')
                    ->label('Batasan Periode')
                    ->columnSpanFull()
                    ->dehydrated(false)
                    ->content(function (Get $get) {
                        $periode = $get('periode');

                        if (! $periode) {
                            return new HtmlString('<span class="text-gray-400">Silakan pilih periode terlebih dahulu.</span>');
                        }

                        // Cari deadline berdasarkan tahun pada batas_waktu
                        $deadline = EkuDeadline::whereYear('batas_waktu', $periode)->first();

                        if ($deadline && $deadline->batas_waktu) {
                            $waktu = $deadline->batas_waktu instanceof Carbon ? $deadline->batas_waktu : Carbon::parse($deadline->batas_waktu);
                            $tanggal = $waktu->locale('id')->translatedFormat('l, d F Y');

                            $isExpired = now()->isAfter($waktu);
                            $colorClass = $isExpired ? 'text-red-600 font-bold' : 'text-emerald-600 font-semibold';
                            $statusText = $isExpired ? ' (Sudah Berakhir)' : ' (Masih Berlaku)';

                            return new HtmlString("<span class=\"{$colorClass}\">Batas Pengajuan: {$tanggal}{$statusText}</span>");
                        }

                        return new HtmlString('<span class="text-gray-400">Belum ditentukan oleh BI untuk periode ini.</span>');
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
