<?php

namespace App\Filament\Resources\EkuTransactions\Schemas;

use App\Models\EkuDeadline;
use App\Models\EkuTransaction;
use App\Support\CurrentUser;
use App\Support\UploadedFileNaming;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class EkuTransactionForm
{
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
                    ->options(function () {
                        // Pilihan tahun tidak lagi diturunkan dari baris
                        // EkuDeadline (itu sumber bug-nya) -- cukup rentang
                        // tahun berjalan s.d. 2 tahun ke depan.
                        $tahunSekarang = (int) now()->year;
                        $options = [];

                        foreach (range($tahunSekarang - 1, $tahunSekarang + 2) as $tahun) {
                            $options[(string) $tahun] = (string) $tahun;
                        }

                        return $options;
                    })
                    ->default(fn () => (string) now()->year)
                    // Batas Pengajuan EKU sekarang berlaku GLOBAL (satu
                    // tanggal untuk semua periode) -- begitu lewat, SEMUA
                    // periode ikut tertutup untuk User Perbankan, bukan
                    // hanya periode tertentu.
                    ->disableOptionWhen(function (): bool {
                        $user = CurrentUser::get();

                        return $user?->isUserPerbankan() && EkuDeadline::isTertutup();
                    })
                    ->rule(function () {
                        return function (string $attribute, $value, \Closure $fail) {
                            $user = CurrentUser::get();

                            if ($user?->isUserPerbankan() && EkuDeadline::isTertutup()) {
                                $fail('Batas waktu pengajuan EKU sudah berakhir. Silakan bersurat resmi ke Bank Indonesia untuk permohonan perpanjangan masa waktu pengajuan.');
                            }
                        };
                    }),

                Placeholder::make('batasan_periode_info')
                    ->label('Batasan Periode')
                    ->columnSpanFull()
                    ->dehydrated(false)
                    ->content(function () {
                        $deadline = EkuDeadline::current();

                        if (! $deadline || ! $deadline->batas_waktu) {
                            return new HtmlString('<span class="text-gray-400">Belum ditentukan oleh BI.</span>');
                        }

                        $tanggal = $deadline->batas_waktu->locale('id')->translatedFormat('l, d F Y');
                        $isExpired = $deadline->isSudahLewat();
                        $colorClass = $isExpired ? 'text-red-600 font-bold' : 'text-emerald-600 font-semibold';
                        $statusText = $isExpired ? ' (Sudah Berakhir)' : ' (Masih Berlaku)';

                        return new HtmlString("<span class=\"{$colorClass}\">Batas Pengajuan: {$tanggal}{$statusText}</span>");
                    }),

                FileUpload::make('file_setoran')
                    ->label('File Excel Setoran')
                    ->disk('public')
                    ->directory('pengajuan-eku/setoran')
                    ->visibility('public')
                    ->getUploadedFileNameForStorageUsing(UploadedFileNaming::bersih())
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
                    ->getUploadedFileNameForStorageUsing(UploadedFileNaming::bersih())
                    ->acceptedFileTypes([
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    ])
                    ->maxSize(5120),

                FileUpload::make('file_lampiran')
                    ->label('File Lampiran (PDF / Pendukung)')
                    ->disk('public')
                    ->directory('pengajuan-eku/lampiran')
                    ->getUploadedFileNameForStorageUsing(UploadedFileNaming::bersih())
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
