<?php

namespace App\Filament\Pages;

use App\Models\Bank;
use App\Models\EkuDeadline;
use App\Models\EkuTemplate;
use App\Support\CurrentUser;
use App\Support\EkuExcelParser;
use App\Support\UploadedFileNaming;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use App\Filament\Pages\ViewBatasanBank;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ManagementEku extends Page implements HasForms, HasTable
{
    use InteractsWithForms, InteractsWithTable;

    protected string $view = 'filament.pages.management-eku';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Management EKU';

    protected static ?string $title = 'Management EKU';

    public static function canAccess(): bool
    {
        return CurrentUser::get()?->isAdminBi() ?? false;
    }

    public $tanggal_deadline;
    public $keterangan_deadline;
    public ?array $data = [];

    public function mount(): void
    {
        $deadline = EkuDeadline::current();

        $this->tanggal_deadline = $deadline?->batas_waktu?->format('Y-m-d');
        $this->keterangan_deadline = $deadline?->keterangan;

        $this->form->fill();
    }

    // --- KONFIGURASI TABEL BATASAN BANK ---
    public function table(Table $table): Table
    {
        return $table
            ->query(Bank::query()->orderBy('name'))
            ->columns([
                TextColumn::make('index')
                    ->label('No')
                    ->rowIndex(),

                TextColumn::make('name')
                    ->label('Nama Bank')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('batasan_setoran')
                    ->label('Nilai Batasan Setoran')
                    ->numeric(0, ',', '.')
                    ->placeholder('Tanpa batasan'),

                TextColumn::make('batasan_penarikan')
                    ->label('Nilai Batasan Penarikan')
                    ->numeric(0, ',', '.')
                    ->placeholder('Tanpa batasan'),

                // KOLOM UPLOAD FILE BATASAN SETORAN
                TextColumn::make('file_batasan_setoran')
                    ->label('File Batasan Setoran')
                    ->getStateUsing(fn ($record) => $record->file_batasan_setoran ? 'Ubah File' : 'Upload Excel')
                    ->badge()
                    ->color(fn ($record) => $record->file_batasan_setoran ? 'success' : 'danger')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->action(
                        Action::make('upload_setoran')
                            ->modalHeading(fn (Bank $record) => 'Upload File Batasan Setoran - ' . $record->name)
                            ->modalDescription('Unggah file Excel format EKU. File ini akan digunakan sebagai rujukan saat tombol "Sesuaikan Batasan" ditekan.')
                            ->form([
                                FileUpload::make('file_batasan_setoran')
                                    ->label('Pilih File Excel')
                                    ->disk('public')
                                    ->directory('batasan-bank/setoran')
                                    ->getUploadedFileNameForStorageUsing(UploadedFileNaming::bersih())
                                    ->acceptedFileTypes([
                                        'application/vnd.ms-excel',
                                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                    ])
                                    ->maxSize(5120)
                                    ->default(fn (Bank $record) => $record->file_batasan_setoran)
                            ])
                            ->action(function (Bank $record, array $data) {
                                $path = $data['file_batasan_setoran'];

                                // Sebelumnya cuma path file yang disimpan, totalnya
                                // (batasan_setoran) tidak pernah dihitung ulang --
                                // makanya nilainya tetap 0 di mana pun ditampilkan.
                                // Sekarang parsing-nya memakai logic yang SAMA PERSIS
                                // dengan pembacaan file pengajuan EKU (forecast).
                                $record->update([
                                    'file_batasan_setoran' => $path,
                                    'file_batasan_setoran_nama_asli' => basename($path),
                                    'batasan_setoran' => EkuExcelParser::totalDariFile($path),
                                ]);

                                // Kalau ada pengajuan EKU bank ini yang sudah melebihi
                                // batasan baru, langsung disesuaikan otomatis.
                                $record->ekuTransactions()->get()->each(
                                    fn ($transaksi) => $transaksi->terapkanBatasanBank()
                                );

                                Notification::make()->title('File Batasan Setoran berhasil diunggah dan totalnya dihitung ulang!')->success()->send();
                            })
                    ),

                // KOLOM UPLOAD FILE BATASAN PENARIKAN
                TextColumn::make('file_batasan_penarikan')
                    ->label('File Batasan Penarikan')
                    ->getStateUsing(fn ($record) => $record->file_batasan_penarikan ? 'Ubah File' : 'Upload Excel')
                    ->badge()
                    ->color(fn ($record) => $record->file_batasan_penarikan ? 'success' : 'danger')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->action(
                        Action::make('upload_penarikan')
                            ->modalHeading(fn (Bank $record) => 'Upload File Batasan Penarikan - ' . $record->name)
                            ->modalDescription('Unggah file Excel format EKU. File ini akan digunakan sebagai rujukan saat tombol "Sesuaikan Batasan" ditekan.')
                            ->form([
                                FileUpload::make('file_batasan_penarikan')
                                    ->label('Pilih File Excel')
                                    ->disk('public')
                                    ->directory('batasan-bank/penarikan')
                                    ->getUploadedFileNameForStorageUsing(UploadedFileNaming::bersih())
                                    ->acceptedFileTypes([
                                        'application/vnd.ms-excel',
                                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                    ])
                                    ->maxSize(5120)
                                    ->default(fn (Bank $record) => $record->file_batasan_penarikan)
                            ])
                            ->action(function (Bank $record, array $data) {
                                $path = $data['file_batasan_penarikan'];

                                $record->update([
                                    'file_batasan_penarikan' => $path,
                                    'file_batasan_penarikan_nama_asli' => basename($path),
                                    'batasan_penarikan' => EkuExcelParser::totalDariFile($path),
                                ]);

                                $record->ekuTransactions()->get()->each(
                                    fn ($transaksi) => $transaksi->terapkanBatasanBank()
                                );

                                Notification::make()->title('File Batasan Penarikan berhasil diunggah dan totalnya dihitung ulang!')->success()->send();
                            })
                    ),
            ])
            ->actions([
                Action::make('detail')
                ->label('Detail')
                ->icon('heroicon-o-eye')
                ->color('gray')
                ->url(fn (Bank $record): string => ViewBatasanBank::getUrl([
                    'record' => $record->id
                ])),

                EditAction::make()
                    ->label('Edit')
                    ->modalHeading('Atur Nominal Batasan Manual')
                    ->form([
                        TextInput::make('batasan_setoran')
                            ->label('Batasan Setoran (Rp)')
                            ->numeric()
                            ->placeholder('Kosongkan jika tanpa batasan'),

                        TextInput::make('batasan_penarikan')
                            ->label('Batasan Penarikan (Rp)')
                            ->numeric()
                            ->placeholder('Kosongkan jika tanpa batasan'),
                    ])
                    ->after(function (Bank $record) {
                        $record->ekuTransactions()->get()->each(
                            fn ($transaksi) => $transaksi->terapkanBatasanBank()
                        );
                    }),

                Action::make('delete_batasan')
                    ->label('Delete')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Batasan Bank')
                    ->modalDescription('Apakah Anda yakin ingin menghapus semua nilai nominal dan file batasan untuk bank ini? (Data Bank tidak akan terhapus)')
                    ->action(function (Bank $record) {
                        $record->update([
                            'batasan_setoran' => null,
                            'batasan_penarikan' => null,
                            'file_batasan_setoran' => null,
                            'file_batasan_penarikan' => null,
                        ]);
                        Notification::make()->title('Batasan berhasil dihapus!')->success()->send();
                    }),
            ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Template Setoran')
                    ->schema([
                        FileUpload::make('file_setoran')
                            ->label('File Template Setoran (Excel)')
                            ->disk('public')
                            ->directory('template-eku')
                            ->getUploadedFileNameForStorageUsing(UploadedFileNaming::bersih())
                            ->acceptedFileTypes([
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            ])
                            ->maxSize(5120),
                    ]),

                Section::make('Template Penarikan')
                    ->schema([
                        FileUpload::make('file_penarikan')
                            ->label('File Template Penarikan (Excel)')
                            ->disk('public')
                            ->directory('template-eku')
                            ->getUploadedFileNameForStorageUsing(UploadedFileNaming::bersih())
                            ->acceptedFileTypes([
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            ])
                            ->maxSize(5120),
                    ]),
            ])
            ->statePath('data');
    }

    /**
     * PERBAIKAN: sebelumnya method ini SELALU membuat baris baru
     * (EkuDeadline::create), jadi kalau Admin BI edit ulang deadline
     * berkali-kali, tabelnya menumpuk banyak baris -- dan pengecekan di
     * form pengajuan bank ternyata mengambil baris PALING PERTAMA/lama
     * (bukan yang terbaru), jadi perubahan deadline tidak pernah kepakai.
     *
     * Sekarang deadline HANYA SATU baris untuk seluruh aplikasi: kalau
     * sudah ada, di-update di tempat; kalau belum ada, baru dibuatkan.
     */
    public function simpanDeadline(): void
    {
        $this->validate([
            'tanggal_deadline' => 'required|date',
            'keterangan_deadline' => 'nullable|string|max:255',
        ]);

        $deadline = EkuDeadline::current();

        if ($deadline) {
            $deadline->update([
                'batas_waktu' => $this->tanggal_deadline,
                'keterangan' => $this->keterangan_deadline,
                'created_by' => Auth::id(),
            ]);
        } else {
            EkuDeadline::create([
                'batas_waktu' => $this->tanggal_deadline,
                'keterangan' => $this->keterangan_deadline,
                'created_by' => Auth::id(),
            ]);
        }

        Notification::make()
            ->title('Batas Pengajuan EKU berhasil disimpan')
            ->success()
            ->send();
    }

    public function hapusDeadline($id): void
    {
        EkuDeadline::findOrFail($id)->delete();

        $this->tanggal_deadline = null;
        $this->keterangan_deadline = null;

        Notification::make()
            ->title('Batas Pengajuan EKU berhasil dihapus')
            ->success()
            ->send();
    }

    public function save(): void
    {
        $state = $this->form->getState();

        if (! empty($state['file_setoran'])) {
            EkuTemplate::create([
                'nama_file' => basename($state['file_setoran']),
                'jenis' => EkuTemplate::JENIS_SETORAN,
                'file_path' => $state['file_setoran'],
                'uploaded_by' => Auth::id(),
            ]);
        }

        if (! empty($state['file_penarikan'])) {
            EkuTemplate::create([
                'nama_file' => basename($state['file_penarikan']),
                'jenis' => EkuTemplate::JENIS_PENARIKAN,
                'file_path' => $state['file_penarikan'],
                'uploaded_by' => Auth::id(),
            ]);
        }

        Notification::make()
            ->title('Template EKU berhasil diperbarui')
            ->success()
            ->send();

        $this->form->fill();
    }

    public function hapusTemplate($id): void
    {
        EkuTemplate::findOrFail($id)->delete();

        Notification::make()
            ->title('Template EKU berhasil dihapus')
            ->success()
            ->send();
    }
}
