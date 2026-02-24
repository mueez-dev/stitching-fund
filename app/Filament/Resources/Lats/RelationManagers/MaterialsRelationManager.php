<?php

namespace App\Filament\Resources\Lats\RelationManagers;

use Filament\Forms;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Actions\AttachAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DetachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\Summarizers\Sum;

class MaterialsRelationManager extends RelationManager
{
    protected static string $relationship = 'materials';

    protected static ?string $recordTitleAttribute = 'material';
    
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                 Forms\Components\DatePicker::make('dated')
                ->default(now())
                ->required(),
                Forms\Components\TextInput::make('material')->required(),
                Forms\Components\TextInput::make('colour')->required(),
                Forms\Components\Select::make('unit')
                 ->options([
                    'yards' => 'Yards',
                    'meters' => 'Meters',
                    'packet' => 'Packet',
                    'pieces' => 'Pieces',
                    'roll' => 'Roll',
                    'cone' => 'Cone',
                ])
                ->required(),
                Forms\Components\TextInput::make('rate')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, callable $set, $get) {
                        $quantity = $get('quantity');
                        if ($quantity) {
                            $set('price', $state * $quantity);
                        }
                    }),
                Forms\Components\TextInput::make('quantity')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, callable $set, $get) {
                        $rate = $get('rate');
                        if ($rate) {
                            $set('price', $state * $rate);
                        }
                    }),
                Forms\Components\TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->disabled()
                    ->dehydrated(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('dated')->date(),
                TextColumn::make('material')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('colour')
                    ->searchable(),
                TextColumn::make('unit'),
                
                TextColumn::make('rate')
                    ->numeric(0)
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format($state, 0, '.', ',')),
                TextColumn::make('quantity')
                    ->numeric(0)
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format($state, 0, '.', ',')),
                TextColumn::make('price')
                    ->money('PKR')
                    ->numeric(0)
                    ->sortable()
                    ->formatStateUsing(fn ($state) => 'PKR ' . number_format($state, 0, '.', ','))
                    ->summarize(Sum::make('price')
                        ->formatStateUsing(fn (string $state) => 'Total: PKR ' . number_format($state, 0, '.', ','))
                    )
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Add Material')
                    ->icon('heroicon-o-plus')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['price'] = $data['rate'] * $data['quantity'];
                        // Ensure dated is included in the data
                        if (!isset($data['dated'])) {
                            $data['dated'] = now();
                        }
                        return $data;
                    }),
            ])
            ->actions([
                EditAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['price'] = $data['rate'] * $data['quantity'];
                        // Ensure dated is included in the data
                        if (!isset($data['dated'])) {
                            $data['dated'] = now();
                        }
                        return $data;
                    }),
                DetachAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DetachBulkAction::make(),
                ]),
            ]);
}
}

