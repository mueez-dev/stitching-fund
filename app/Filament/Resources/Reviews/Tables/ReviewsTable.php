<?php

namespace App\Filament\Resources\Reviews\Tables;

use Filament\Tables\Table;
use App\Filament\Resources\Reviews\Star\StarColumnHelper;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Illuminate\Support\Facades\Auth;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\Action;

class ReviewsTable
{   
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('review_text')
                    ->label('Review')
                    ->limit(50)
                    ->searchable()
                    ->wrap(),
            StarColumnHelper::makeStarRating('rating', 'Rating')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'pending' => 'warning',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'approved' => 'Approved',
                        'pending' => 'Pending',
                        'rejected' => 'Rejected',
                    ]),
                SelectFilter::make('rating')
                    ->label('Rating')
                    ->options([
                        1 => '1 Star',
                        2 => '2 Stars',
                        3 => '3 Stars',
                        4 => '4 Stars',
                        5 => '5 Stars',
                    ]),
            ])
            ->actions([
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => Auth::check() && Auth::user()->role === 'Super Admin' && $record->status === 'pending')
                    ->action(function ($record) {
                        $record->update(['status' => 'approved']);
                    }),
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => Auth::check() && Auth::user()->role === 'Super Admin' && $record->status === 'pending')
                    ->action(function ($record) {
                        $record->update(['status' => 'rejected']);
                    }),
                EditAction::make()
                    ->visible(fn () => Auth::check() && Auth::user()->role === 'Super Admin'),
                DeleteAction::make()
                    ->visible(fn () => Auth::check() && Auth::user()->role === 'Super Admin'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => Auth::check() && Auth::user()->role === 'Super Admin'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}