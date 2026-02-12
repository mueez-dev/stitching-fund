<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Filters\Filter;
use Illuminate\Support\Facades\Auth;
use STS\FilamentImpersonate\Actions\Impersonate;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;


class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                ->searchable()
                ->sortable(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('role')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('invited_by')
                    ->label('Agency ID')
                    ->placeholder('No ID')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('investors_count')
                    ->label('Investors Under This Agency')
                    ->placeholder('0')
                    ->searchable()
                    ->sortable()
                    ->getStateUsing(function ($record) {
                        try {
                            // Use the User model relationship we defined
                            $count = $record->investors()->count();
                            return $count . ' Investors';
                        } catch (\Exception $e) {
                            return '0';
                        }
                    }),
                ToggleColumn::make('status')
                    ->label('Active')
                    ->sortable()
                    ->getStateUsing(function ($record) {
                        return $record->status === 'active';
                    })
                    ->updateStateUsing(function ($record, $state) {
                        $oldStatus = $record->status;
                        $record->update(['status' => $state ? 'active' : 'inactive']);
                        
                        // If user is being set to inactive, logout their sessions
                        if (!$state && $oldStatus === 'active') {
                            // Logout all sessions for this user
                            \Illuminate\Support\Facades\DB::table('sessions')
                                ->where('user_id', $record->id)
                                ->delete();
                        }
                    }),

            ])
            ->filters([
                Filter::make('name')
                    ->form([
                        TextInput::make('name')
                            ->label('Name'),
                    ])
                    ->query(function ($query, array $data) {
                        $query->when($data['name'] ?? null, function ($query, $name) {
                            $query->where('name', 'like', '%'.$name.'%');
                        });
                    }),
                Filter::make('email')
                    ->form([
                        TextInput::make('email')
                            ->label('Email'),
                    ])
                    ->query(function ($query, array $data) {
                        $query->when($data['email'] ?? null, function ($query, $email) {
                            $query->where('email', 'like', '%'.$email.'%');
                        });
                    }),
                SelectFilter::make('role')
                    ->label('Role')
                    ->options([
                        'invester' => 'Investor',
                        'agency owner' => 'Agency Owner',
                        'user' => 'User',
                    ]),
            ])   
              
          
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->visible(fn ($record) => $record?->role !== 'Super Admin'),
                 // Custom Impersonate Action - CORRECTED
            Impersonate::make('impersonate')
                    ->label('Switch to User')
                    ->icon('heroicon-o-arrow-right-circle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Confirm Impersonation')
                    ->modalDescription('Are you sure you want to impersonate this user? You will be able to switch back at any time.')
                    ->modalSubmitActionLabel('Yes, Switch')
                    ->impersonateRecord(fn($record) => $record)
                    ->visible(fn ($record) => 
                        Auth::user()->role === 'Super Admin' && 
                        $record->role !== 'Super Admin' &&
                        Auth::id() !== $record->id
                    ),
                    
              
            ])
            ->poll(10);
           
    }
}