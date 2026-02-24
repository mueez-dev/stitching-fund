<?php

namespace App\Filament\Resources\Lats\RelationManagers;

use Filament\Forms;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\Summarizers\Sum;

class ExpenseRelationManager extends RelationManager
{
    protected static string $relationship = 'expenses';

    protected static ?string $recordTitleAttribute = 'labour_type';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Hidden::make('lat_id')
                    ->default(fn ($livewire) => $livewire->ownerRecord->id)
                    ->required(),
                Forms\Components\DatePicker::make('dated')
                    ->default(now())
                    ->required(),
                Forms\Components\TextInput::make('labour_type')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('unit')
                    ->options([
                        '1' => 'Yards',
                        '2' => 'Pieces',
                    ])
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, callable $set, $get) {
                        $rate = (float) $get('rate');
                        if ($rate) {
                            $set('price', $rate);
                        }
                    }),
                Forms\Components\TextInput::make('rate')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, callable $set, $get) {
                        $pieces = (float) $get('pieces');
                        if ($pieces) {
                            $set('price', $state * $pieces);
                        }
                    }),
                Forms\Components\TextInput::make('pieces')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, callable $set, $get) {
                        $rate = (float) $get('rate');
                        if ($rate) {
                            $set('price', $rate * $state);
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
                TextColumn::make('dated')
                    ->date(),
                TextColumn::make('labour_type')
                    ->searchable(),
                TextColumn::make('unit')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state == 1 ? 'Yards' : 'Pieces'),
                TextColumn::make('rate')
                    ->money('PKR')
                    ->numeric(0)
                    ->sortable()
                    ->formatStateUsing(fn ($state) => 'PKR ' . number_format($state, 0, '.', ',')),
                TextColumn::make('pieces')
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
                    ->label('Add Expense')
                    ->icon('heroicon-o-plus')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['price'] = $data['unit'] * $data['rate'];
                        return $data;
                    })
                    ->using(function (array $data, string $model): Model {
                        return $this->getOwnerRecord()->expenses()->create($data);
                    }),
            ])
            ->actions([
                EditAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['price'] = $data['unit'] * $data['rate'];
                        return $data;
                    }),
                DeleteAction::make(),
            ]);
    }
}
