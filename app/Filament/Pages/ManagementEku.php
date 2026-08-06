<?php

namespace App\Filament\Pages;

use App\Imports\BankLimitImport;
use App\Models\Bank;
use App\Models\EkuDeadline;
use App\Models\EkuTemplate;
use App\Support\CurrentUser;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class ManagementEku extends Page implements HasForms
{
    use InteractsWithForms;

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
    public array $batasanBank = [];
    public ?array $data = [];

    public function mount(): void
    {
        $deadline = EkuDeadline::current();

        $this->tanggal_deadline = $deadline?->batas_waktu?->format('Y-m-d');
        $this->keterangan_deadline = $deadline?->keterangan;

        foreach (Bank::query()->orderBy('name')->get() as $bank) {
            $this->batasanBank[$bank->id] = [
                'batasan_setoran' => $bank->batasan_setoran,
                'batasan_penarikan' => $bank->batasan_penarikan,
            ];
        }

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

                // Form untuk Upload File Excel Batasan Bank
                Section::make('Import Batasan EKU per Bank (Excel)')
                    ->schema([
                        FileUpload::make('file_batasan_bank')
                            ->label('File Excel Batasan Bank')
                            ->disk('public')
                            ->directory('temp-excel')
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

    public function simpanBatasanBank(int $bankId): void
    {
        $input = $this->batasanBank[$bankId] ?? [];

        $this->validate([
            "batasanBank.{$bankId}.batasan_setoran" => 'nullable|numeric|min:0',
            "batasanBank.{$bankId}.batasan_penarikan" => 'nullable|numeric|min:0',
        ]);

        $bank = Bank::findOrFail($bankId);

        $bank->update([
            'batasan_setoran' => ($input['batasan_setoran'] ?? null) !== '' ? ($input['batasan_setoran'] ?? null) : null,
            'batasan_penarikan' => ($input['batasan_penarikan'] ?? null) !== '' ? ($input['batasan_penarikan'] ?? null) : null,
        ]);

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

    // Fungsi untuk memproses file Excel yang di-upload
    public function importBatasanBank(): void
    {
        $state = $this->form->getState();

        if (empty($state['file_batasan_bank'])) {
            Notification::make()->title('Silakan unggah file Excel terlebih dahulu')->danger()->send();
            return;
        }

        $filePath = storage_path('app/public/' . $state['file_batasan_bank']);

        Excel::import(new BankLimitImport, $filePath);

        Notification::make()
            ->title('Batasan EKU per Bank berhasil diimpor!')
            ->success()
            ->send();

        $this->mount();
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
