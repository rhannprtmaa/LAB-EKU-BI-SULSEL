<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\Bank;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Akun')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Lengkap')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->label('Alamat Email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Select::make('role')
                            ->label('Role Akses')
                            ->options([
                                'admin_bi' => 'Admin BI',
                                'user_bi' => 'User BI (Operational)',
                                'user_perbankan' => 'User Perbankan',
                            ])
                            ->required()
                            ->live(),

                        Select::make('bank_id')
                            ->label('Asal Bank')
                            ->options(fn () => Bank::query()
                                ->where('is_active', true)
                                ->pluck('name', 'id')
                                ->toArray())
                            ->searchable()
                            ->visible(fn (Get $get): bool => $get('role') === 'user_perbankan')
                            ->required(fn (Get $get): bool => $get('role') === 'user_perbankan'),

                        TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->dehydrateStateUsing(fn (?string $state) => filled($state) ? Hash::make($state) : null)
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->requiredOn('create'),

                        Toggle::make('is_active')
                            ->label('Status Akun Aktif')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }
}
