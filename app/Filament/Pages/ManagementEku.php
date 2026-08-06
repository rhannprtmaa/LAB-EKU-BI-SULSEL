<?php

namespace App\Filament\Pages;

use App\Models\Bank;
use App\Models\EkuDeadline;
use App\Models\EkuTemplate;
use App\Support\CurrentUser;
use App\Support\EkuExcelParser;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Livewire\WithFileUploads;

class ManagementEku extends Page implements HasForms
{
    use InteractsWithForms;
    use WithFileUploads;

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

    // File batasan yang sedang dipilih user (belum disimpan), key = id bank.
    // Livewire\WithFileUploads menangani ini sebagai TemporaryUploadedFile.
    public array $fileBatasanSetoran = [];

    public array $fileBatasanPenarikan = [];

    public ?array $data = [];

    public function mount(): void
    {
        $deadline = EkuDeadline::current();

        $this->tanggal_deadline = $deadline?->batas_waktu?->format('Y-m-d');
        $this->keterangan_deadline = $deadline?->keterangan;

        $this->form->fill();
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
                            ->acceptedFileTypes([
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            ])
                            ->maxSize(5120),
                    ]),
            ])
            ->statePath('data');
    }

    public function simpanDeadline(): void
    {
        $this->validate([
            'tanggal_deadline' => 'required|date',
            'keterangan_deadline' => 'nullable|string|max:255',
        ]);

        EkuDeadline::create([
            'batas_waktu' => $this->tanggal_deadline,
            'keterangan' => $this->keterangan_deadline,
            'created_by' => Auth::id(),
        ]);

        Notification::make()
            ->title('Batas Pengajuan EKU berhasil disimpan')
            ->success()
            ->send();

        $this->keterangan_deadline = null;
    }

    public function hapusDeadline($id): void
    {
        EkuDeadline::findOrFail($id)->delete();

        Notification::make()
            ->title('Batas Pengajuan EKU berhasil dihapus')
            ->success()
            ->send();
    }

    /**
     * Simpan batasan EKU untuk satu bank lewat file Excel (format-nya SAMA
     * PERSIS dengan Template Kerja EKU yang dipakai bank untuk pengajuan --
     * bukan angka manual). Totalnya dihitung otomatis dari isi file, lalu
     * disimpan sebagai batasan_setoran / batasan_penarikan.
     *
     * Kalau ada pengajuan EKU bank ini yang nilainya sudah melebihi batasan
     * baru, otomatis disesuaikan (lihat EkuTransaction::terapkanBatasanBank()).
     */
    public function simpanBatasanBank(int $bankId): void
    {
        $this->validate([
            "fileBatasanSetoran.{$bankId}" => 'nullable|file|mimes:xls,xlsx|max:5120',
            "fileBatasanPenarikan.{$bankId}" => 'nullable|file|mimes:xls,xlsx|max:5120',
        ]);

        $fileSetoran = $this->fileBatasanSetoran[$bankId] ?? null;
        $filePenarikan = $this->fileBatasanPenarikan[$bankId] ?? null;

        if (! $fileSetoran && ! $filePenarikan) {
            Notification::make()
                ->title('Pilih minimal satu file (Batasan Setoran atau Penarikan) terlebih dahulu')
                ->warning()
                ->send();

            return;
        }

        $bank = Bank::findOrFail($bankId);
        $dataUpdate = [];

        if ($fileSetoran) {
            $namaFile = date('YmdHis') . '_' . $fileSetoran->getClientOriginalName();
            $path = $fileSetoran->storeAs('batasan-eku/setoran', $namaFile, 'public');

            $dataUpdate['file_batasan_setoran'] = $path;
            $dataUpdate['file_batasan_setoran_nama_asli'] = $fileSetoran->getClientOriginalName();
            $dataUpdate['batasan_setoran'] = EkuExcelParser::totalDariFile($path);
        }

        if ($filePenarikan) {
            $namaFile = date('YmdHis') . '_' . $filePenarikan->getClientOriginalName();
            $path = $filePenarikan->storeAs('batasan-eku/penarikan', $namaFile, 'public');

            $dataUpdate['file_batasan_penarikan'] = $path;
            $dataUpdate['file_batasan_penarikan_nama_asli'] = $filePenarikan->getClientOriginalName();
            $dataUpdate['batasan_penarikan'] = EkuExcelParser::totalDariFile($path);
        }

        $bank->update($dataUpdate);

        unset($this->fileBatasanSetoran[$bankId], $this->fileBatasanPenarikan[$bankId]);

        // Terapkan batasan baru ke pengajuan EKU bank ini -- kalau ada yang
        // melebihi, otomatis disesuaikan (di-scale turun) supaya sesuai batasan.
        $jumlahDisesuaikan = $bank->ekuTransactions()
            ->get()
            ->reduce(function (int $carry, $transaksi) {
                return $carry + ($transaksi->terapkanBatasanBank() ? 1 : 0);
            }, 0);

        Notification::make()
            ->title('Batasan EKU untuk ' . $bank->name . ' berhasil disimpan')
            ->body($jumlahDisesuaikan > 0
                ? "{$jumlahDisesuaikan} pengajuan EKU otomatis disesuaikan karena melebihi batasan baru."
                : null)
            ->success()
            ->send();
    }

    public function hapusBatasanBank(int $bankId, string $jenis): void
    {
        $bank = Bank::findOrFail($bankId);

        if ($jenis === 'setoran') {
            $bank->update([
                'file_batasan_setoran' => null,
                'file_batasan_setoran_nama_asli' => null,
                'batasan_setoran' => null,
            ]);
        } else {
            $bank->update([
                'file_batasan_penarikan' => null,
                'file_batasan_penarikan_nama_asli' => null,
                'batasan_penarikan' => null,
            ]);
        }

        Notification::make()
            ->title('Batasan ' . ucfirst($jenis) . ' untuk ' . $bank->name . ' dihapus')
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

    public function templateSetoran(): ?EkuTemplate
    {
        return EkuTemplate::current(EkuTemplate::JENIS_SETORAN);
    }

    public function templatePenarikan(): ?EkuTemplate
    {
        return EkuTemplate::current(EkuTemplate::JENIS_PENARIKAN);
    }

    public function batasSaatIni(): ?EkuDeadline
    {
        return EkuDeadline::current();
    }

    public function daftarBank()
    {
        return Bank::query()->orderBy('name')->get();
    }
}
