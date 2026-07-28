<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')
                    ->label('Nama Pengguna')
                    ->required()
                    ->maxLength(255),

                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->maxLength(255),

                TextInput::make('password')
                    ->label('Password')
                    ->password()
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $operation): bool => $operation === 'create'),

                Select::make('role')
                    ->label('Role Akses')
                    ->options([
                        'admin' => 'Admin BI',
                        'bank' => 'User Bank',
                    ])
                    ->required()
                    ->reactive(),

                // Pemilihan Bank terintegrasi langsung di sini
                Select::make('bank_id')
                    ->label('Asal Bank')
                    ->relationship('bank', 'name')
                    ->searchable()
                    ->preload()
                    ->visible(fn ($get) => $get('role') === 'bank') // Muncul jika role = User Bank
                    ->createOptionForm([
                        TextInput::make('name')
                            ->label('Nama Bank Baru')
                            ->required(),
                        TextInput::make('code')
                            ->label('Kode Bank')
                            ->required(),
                    ]),
            ]);
    }
}
