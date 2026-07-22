<?php

namespace App\Filament\Pages;

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

class KelolaTemplateEku extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.kelola-template-eku';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-arrow-up';

    protected static ?string $navigationLabel = 'Template Kerja EKU';
    protected static ?string $title = 'Kelola Template Kerja EKU';

    public static function canAccess(): bool
    {
        return CurrentUser::get()?->isAdminBi() ?? false;
    }

    public ?array $data = [];

    public function mount(): void
    {
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

        Notification::make()->title('Template kerja EKU berhasil diperbarui')->success()->send();

        $this->form->fill();
    }

    public function templateSetoran(): ?EkuTemplate
    {
        return EkuTemplate::current(EkuTemplate::JENIS_SETORAN);
    }

    public function templatePenarikan(): ?EkuTemplate
    {
        return EkuTemplate::current(EkuTemplate::JENIS_PENARIKAN);
    }
}
