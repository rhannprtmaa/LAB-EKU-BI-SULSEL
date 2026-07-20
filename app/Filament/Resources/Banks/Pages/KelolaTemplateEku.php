<?php

namespace App\Filament\Pages;

use App\Models\EkuTemplate;
use App\Support\CurrentUser;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
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
                FileUpload::make('file_path')
                    ->label('File Template Kerja EKU (Excel)')
                    ->directory('template-eku')
                    ->acceptedFileTypes([
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    ])
                    ->required()
                    ->maxSize(5120),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        EkuTemplate::create([
            'nama_file' => basename($state['file_path']),
            'file_path' => $state['file_path'],
            'uploaded_by' => Auth::id(),
        ]);

        Notification::make()
            ->title('Template kerja EKU berhasil diperbarui')
            ->success()
            ->send();

        $this->form->fill();
    }

    public function currentTemplate(): ?EkuTemplate
    {
        return EkuTemplate::current();
    }
}
