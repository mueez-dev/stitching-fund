<?php

namespace App\Filament\Resources\Reviews\Pages;

use App\Filament\Resources\Reviews\ReviewResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;
use App\Models\Review;

class ListReviews extends ListRecords
{
    protected static string $resource = ReviewResource::class;

    protected function getHeaderActions(): array
    {
        $actions = [];
        
        // Only show CreateAction if user is authenticated, not in grace period, and has no existing review
        if (Auth::check()) {
            $isGrace = Auth::user()?->getSubscriptionState() === 'expired_grace';
            $existingReview = Review::where('user_id', Auth::id())->first();
            
            if (!$isGrace && !$existingReview) {
                $actions[] = CreateAction::make();
            }
        }
        
        return $actions;
    }
}
