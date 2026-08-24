<?php

namespace App\Filament\Resources\KnowledgePosts;

use App\Filament\Resources\KnowledgePosts\Pages;
use App\Models\KnowledgePost;
use App\Support\CurrentUser;
use App\Support\UploadedFileNaming;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class KnowledgePostResource extends Resource
{
    protected static ?string $model = KnowledgePost::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationLabel = 'Kelola Knowledge Center';
    protected static ?string $modelLabel = 'Materi Knowledge Center';
    protected static ?int $navigationSort = 4;

    // Hanya Admin BI yang boleh mengelola (buat/edit/hapus) materi Knowledge
    // Center. User BI dan User Perbankan hanya menerima/melihatnya lewat
    // halaman "Knowledge Center" (App\Filament\Pages\KnowledgeCenter).
    public static function canViewAny(): bool
    {
        return CurrentUser::get()?->isAdminBi() ?? false;
    }

    public static function canCreate(): bool
    {
        return CurrentUser::get()?->isAdminBi() ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return CurrentUser::get()?->isAdminBi() ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return CurrentUser::get()?->isAdminBi() ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return CurrentUser::get()?->isAdminBi() ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('judul')
                    ->label('Judul Berita / Panduan')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, callable $set) => $state ? $set('slug', Str::slug($state)) : null)
                    ->columnSpanFull(),

                TextInput::make('slug')
                    ->required()
                    ->disabled()
                    ->dehydrated(),

                Select::make('kategori')
                    ->label('Kategori')
                    ->options(KnowledgePost::kategoriOptions())
                    ->required(),

                Textarea::make('ringkasan')
                    ->label('Ringkasan Singkat')
                    ->rows(2)
                    ->columnSpanFull(),

                RichEditor::make('konten')
                    ->label('Isi Konten / Berita')
                    ->required()
                    ->columnSpanFull(),

                FileUpload::make('gambar_sampul')
                    ->label('Gambar Sampul (Thumbnail)')
                    ->disk('public')
                    ->directory('knowledge-center/thumbnails')
                    ->getUploadedFileNameForStorageUsing(UploadedFileNaming::bersih())
                    ->image()
                    ->columnSpan(1),

                FileUpload::make('file_lampiran')
                    ->label('Dokumen Lampiran (PDF/Excel - Opsional)')
                    ->disk('public')
                    ->directory('knowledge-center/attachments')
                    ->getUploadedFileNameForStorageUsing(UploadedFileNaming::bersih())
                    ->columnSpan(1),

                Toggle::make('is_featured')
                    ->label('Sematkan sebagai Berita Utama (Featured)')
                    ->default(false),

                Toggle::make('is_published')
                    ->label('Publikasikan Sekarang')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('gambar_sampul')->label('Sampul'),
                TextColumn::make('judul')->searchable()->sortable()->limit(40),
                TextColumn::make('kategori')->badge()->color('info'),
                IconColumn::make('is_featured')->boolean()->label('Featured'),
                IconColumn::make('is_published')->boolean()->label('Terbit'),
                TextColumn::make('created_at')->dateTime('d M Y')->label('Dibuat'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKnowledgePosts::route('/'),
            'create' => Pages\CreateKnowledgePost::route('/create'),
            'edit' => Pages\EditKnowledgePost::route('/{record}/edit'),
        ];
    }
}
