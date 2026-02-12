<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms;
use Filament\Schemas\Schema;

class UsersForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->required(fn (string $context): bool => $context === 'create')
                    ->dehydrateStateUsing(fn ($state) => bcrypt($state))
                    ->dehydrated(fn ($state) => filled($state)),
                Forms\Components\Select::make('role')
                    ->options([
                        'Super Admin' => 'Super Admin',
                        'Agency Owner' => 'Agency Owner',
                        'Investor' => 'Investor',
                    ])
                    ->required(),
                Forms\Components\Toggle::make('status')
                    ->label('Active')
                    ->default(true)
                    ->formatStateUsing(function ($state) {
                        return $state === 'active';
                    })
                    ->dehydrateStateUsing(function ($state) {
                        return $state ? 'active' : 'inactive';
                    }),
            ]);
    }
}