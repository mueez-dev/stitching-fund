<?php

namespace App\Filament\Resources\WidthrawlRequests\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;

class WidthrawlRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('wallet_id')
                    ->required()
                    ->numeric()
                    ->disabled()
                    ->columnSpan(1),
                TextInput::make('investor_name')
                    ->required()
                    ->disabled()
                    ->columnSpan(1),
                TextInput::make('requested_amount')
                    ->required()
                    ->numeric()
                    ->prefix('PKR')
                    ->disabled()
                    ->columnSpan(1),
                TextInput::make('approved_amount')
                    ->numeric()
                    ->minValue(0)
                    ->prefix('PKR')
                    ->label('Approved Amount')
                    ->helperText('Leave empty to use requested amount')
                    ->visible(fn ($record) => $record?->status === 'pending')
                    ->columnSpan(1),
                Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ])
                    ->required()
                    ->disabled(fn ($record) => $record && $record->status !== 'pending')
                    ->columnSpan(1),
                Textarea::make('owner_notes')
                    ->label('Owner Notes')
                    ->rows(3)
                    ->placeholder('Add notes for rejection reason...')
                    ->columnSpanFull(),
            ]);
    }
}
