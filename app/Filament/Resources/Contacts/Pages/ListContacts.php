<?php

namespace App\Filament\Resources\Contacts\Pages;

use App\Filament\Resources\Contacts\ContactsResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListContacts extends ListRecords
{
    protected static string $resource = ContactsResource::class;

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
}
