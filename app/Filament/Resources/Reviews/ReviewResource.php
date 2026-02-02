<?php

namespace App\Filament\Resources\Reviews;

use App\Filament\Resources\Reviews\Pages\CreateReview;
use App\Filament\Resources\Reviews\Pages\EditReview;
use App\Filament\Resources\Reviews\Pages\ListReviews;
use App\Filament\Resources\Reviews\Schemas\ReviewForm;
use App\Filament\Resources\Reviews\Tables\ReviewsTable;
use App\Models\Review;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'Review';

    public static function form(Schema $schema): Schema
    {
        return ReviewForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ReviewsTable::configure($table);
    }

    public static function canCreate(): bool
    {
        // Only allow if user is authenticated
        if (!Auth::check()) {
            return false;
        }
        
        // Get current user ID
        $userId = Auth::id();
        
        // Check if user already has a review
        $existingReview = Review::where('user_id', $userId)->first();
        
        // Debug: Log the check (you can remove this after testing)
        Log::info('Review canCreate check - User ID: ' . $userId . ', Existing Review: ' . ($existingReview ? 'Yes' : 'No'));
        
        // Allow creation if no review exists
        return !$existingReview;
    }

    public static function canViewAny(): bool
    {
        // Only authenticated users can view reviews
        return Auth::check();
    }

    public static function canEdit($record): bool
    {
        // Only allow super admin to edit
        return Auth::check() && Auth::user()->role === 'Super Admin';
    }

    public static function canDelete($record): bool
    {
        // Only allow super admin to delete
        return Auth::check() && Auth::user()->role === 'Super Admin';
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReviews::route('/'),
            'create' => CreateReview::route('/create'),
            'edit' => EditReview::route('/{record}/edit'),
        ];
    }
}
