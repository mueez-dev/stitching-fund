<?php

namespace App\Filament\Resources\Contacts\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

class ContactsForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
               TextInput::make('name')
               ->label('Name')
               ->required()
               ->maxLength(255),
               TextInput::make('phone')
               ->label('Phone')
                ->type('tel')
                ->maxLength(11)
                ->minLength(11)
                ->regex('/^03[0-9]{9}$/')
                ->prefixIcon('heroicon-o-phone')
                ->numeric()
               ->required()
               ->unique(),
               Select::make('ctype')
                ->options([
                    'customer' => 'Customer',
                    'investor' => 'Investor',
                    'employee' => 'Employee',
                ])
                ->label('C type')
                ->required(),
               
            ]);
    }
}
