<?php

namespace App\Filament\Resources\Lats\Tables;

use App\Models\Lat;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Facades\Auth;

class LatsTable
{
    public static function configure(Table $table): Table
    {
        $isGrace = Auth::check() && Auth::user()?->getSubscriptionState() === 'expired_grace';

        return $table
            ->query(Lat::with(['materials', 'expenses','summary'])->forUser())
            ->columns([
               TextColumn::make('lat_no')
               ->label('Lat No')
               ->searchable(),
               TextColumn::make('design_name')
               ->label('Design')
               ->searchable(),
               TextColumn::make('customer_name')
               ->label('Customer')
               ->searchable(),
                TextColumn::make('total_price')
                    ->label('Total Price')
                    ->numeric()
                    ->prefix('PKR ')
                    ->sortable()
                    ->state(function (Lat $record) {
                        // Calculate material total using the price column
                        $materialTotal = $record->materials->sum('price');
                        // Calculate expense total
                        $expenseTotal = $record->expenses->sum('price');
                        
                        return $materialTotal + $expenseTotal;
                    })
                    ->formatStateUsing(fn ($state) => number_format($state, 0, '.', ','))
            ])
            ->filters([
                SelectFilter::make('design')
                    ->label('Design')
                    ->relationship('design', 'name')
                    ->searchable(),
                SelectFilter::make('customer')
                    ->label('Customer')
                    ->relationship('customer', 'name')
                    ->searchable(),
                Filter::make('lat_no')
                    ->form([
                        TextInput::make('lat_no')
                            ->label('Lat No')
                            ->numeric(),
                    ])
                    ->query(function ($query, array $data) {
                        return $query->when(
                            $data['lat_no'],
                            fn ($query, $latNo) => $query->where('lat_no', $latNo)
                        );
                    })
            ])
            ->actions([
                EditAction::make()
                    ->disabled($isGrace),
                DeleteAction::make()
                    ->disabled($isGrace),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->disabled($isGrace),
                ]),
            ]);
    }
}