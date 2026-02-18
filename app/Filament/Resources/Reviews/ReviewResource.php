<?php

namespace App\Filament\Resources\Reviews;

use BackedEnum;
use App\Models\Review;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Log;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Resources\Reviews\Pages\EditReview;
use App\Filament\Resources\Reviews\Pages\ListReviews;
use App\Filament\Resources\Reviews\Pages\CreateReview;
use App\Filament\Resources\Reviews\Schemas\ReviewForm;
use App\Filament\Resources\Reviews\Tables\ReviewsTable;

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
    if (!Auth::check()) return false;

    $state = Auth::user()?->getSubscriptionState();
    Log::info('canCreate state: ' . $state . ' user: ' . Auth::id());

    if ($state === 'expired_grace') return false;

    return !Review::where('user_id', Auth::id())->exists();
}

    public static function canViewAny(): bool
    {
        return Auth::check();
    }

    public static function canEdit(Model $record): bool
    {
        if (!Auth::check()) return false;
        if (Auth::user()?->getSubscriptionState() === 'expired_grace') return false;
        return Auth::user()?->role === 'Super Admin';
    }

    public static function canDelete(Model $record): bool
    {
        if (!Auth::check()) return false;
        if (Auth::user()?->getSubscriptionState() === 'expired_grace') return false;
        return Auth::user()?->role === 'Super Admin';
    }

    public static function getPages(): array
    {
        $isGrace = Auth::check() && Auth::user()?->getSubscriptionState() === 'expired_grace';

        if ($isGrace) {
            return [
                'index' => ListReviews::route('/'),
            ];
        }

        return [
            'index'  => ListReviews::route('/'),
            'create' => CreateReview::route('/create'),
            'edit'   => EditReview::route('/{record}/edit'),
        ];
    }
}