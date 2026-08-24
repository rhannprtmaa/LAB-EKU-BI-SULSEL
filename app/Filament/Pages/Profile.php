<?php

namespace App\Filament\Pages;

use App\Support\CurrentUser;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class Profile extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.profile';

    // Halaman ini diakses lewat baris nama akun di dropdown user menu
    // (bawah sidebar), bukan lewat menu navigasi utama.
    protected static bool $shouldRegisterNavigation = false;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $navigationLabel = 'Profil Saya';

    protected static ?string $title = 'Profil Saya';

    // Ditaruh paling bawah navigasi karena bukan menu kerja utama.
    protected static ?int $navigationSort = 99;

    public ?array $passwordData = [];

    public ?array $avatarData = [];

    public function mount(): void
    {
        $user = CurrentUser::get();

        $this->passwordForm->fill();

        $this->avatarForm->fill([
            'avatar_url' => $user?->avatar_url,
        ]);
    }

    protected function getForms(): array
    {
        return [
            'passwordForm',
            'avatarForm',
        ];
    }

    // --- FORM GANTI SANDI ---
    public function passwordForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('current_password')
                    ->label('Sandi Saat Ini')
                    ->password()
                    ->revealable()
                    ->required()
                    ->currentPassword()
                    ->autocomplete('current-password'),

                TextInput::make('password')
                    ->label('Sandi Baru')
                    ->password()
                    ->revealable()
                    ->required()
                    ->minLength(8)
                    ->different('current_password')
                    ->confirmed()
                    ->autocomplete('new-password'),

                TextInput::make('password_confirmation')
                    ->label('Konfirmasi Sandi Baru')
                    ->password()
                    ->revealable()
                    ->required()
                    ->autocomplete('new-password'),
            ])
            ->statePath('passwordData');
    }

    // --- FORM FOTO PROFIL (dijaga ringan agar tidak berat) ---
    public function avatarForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                FileUpload::make('avatar_url')
                    ->label('Foto Profil')
                    ->avatar()
                    ->image()
                    ->imageEditor()
                    ->circleCropper()
                    ->disk('public')
                    ->directory('avatars')
                    ->visibility('public')
                    ->imageResizeMode('cover')
                    ->imageCropAspectRatio('1:1')
                    ->imageResizeTargetWidth('400')
                    ->imageResizeTargetHeight('400')
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(512) // KB -- sengaja dibatasi kecil supaya ringan
                    ->helperText('Format JPG/PNG/WebP, maksimal 512 KB. Foto otomatis dipotong bulat & disesuaikan ukurannya.'),
            ])
            ->statePath('avatarData');
    }

    public function updatePassword(): void
    {
        $data = $this->passwordForm->getState();

        $user = CurrentUser::get();

        $user->update([
            'password' => Hash::make($data['password']),
        ]);

        $this->passwordForm->fill();

        Notification::make()
            ->title('Sandi berhasil diperbarui')
            ->success()
            ->send();
    }

    public function updateAvatar(): void
    {
        $data = $this->avatarForm->getState();

        $user = CurrentUser::get();
        $fileLama = $user->avatar_url;
        $fileBaru = $data['avatar_url'] ?? null;

        $user->update([
            'avatar_url' => $fileBaru,
        ]);

        // Hapus file lama supaya storage tidak menumpuk file foto yang sudah diganti.
        if ($fileLama && $fileLama !== $fileBaru && Storage::disk('public')->exists($fileLama)) {
            Storage::disk('public')->delete($fileLama);
        }

        Notification::make()
            ->title('Foto profil berhasil diperbarui')
            ->success()
            ->send();
    }
}
