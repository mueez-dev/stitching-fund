<?php

namespace App\Filament\Resources\Lats\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Illuminate\Support\Facades\Auth;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\Lats\LatsResource;

class ListLats extends ListRecords
{
    protected static string $resource = LatsResource::class;

    protected function getHeaderActions(): array
    {
        $actions = [];
        
        // Only show CreateAction if user is authenticated and not in grace period
        if (Auth::check()) {
            $isGrace = Auth::user()?->getSubscriptionState() === 'expired_grace';
            
            if (!$isGrace) {
                $actions[] = CreateAction::make();
            }
        }
        
        return $actions;
    }
   

protected function getTableActions(): array
{
    return [
        Action::make('open')
            ->label('Open')
            ->url(fn ($record) => static::getResource()::getUrl('view', ['record' => $record]))
    ];
}

}