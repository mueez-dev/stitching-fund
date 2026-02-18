<?php

namespace App\Filament\Resources\Contacts\Tables;

use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Illuminate\Support\Facades\Auth;

class ContactsTable
{
    public static function configure(Table $table): Table
    {
        $isGrace = Auth::check() && Auth::user()?->getSubscriptionState() === 'expired_grace';

        return $table
            ->columns([
               TextColumn::make('name')
               ->label('Name')
               ->searchable(),
               TextColumn::make('phone')
               ->label('Phone')
               ->searchable(),
               TextColumn::make('ctype')
               ->label('Type')
               ->searchable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()
                    ->disabled($isGrace),
                DeleteAction::make()
                    ->disabled($isGrace),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->disabled($isGrace),
                ]),
            ]);
    }
}
