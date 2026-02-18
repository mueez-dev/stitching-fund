<?php

namespace App\Filament\Resources\WidthrawlRequests;

use App\Filament\Resources\WidthrawlRequests\Pages\CreateWidthrawlRequest;
use App\Filament\Resources\WidthrawlRequests\Pages\EditWidthrawlRequest;
use App\Filament\Resources\WidthrawlRequests\Pages\ListWidthrawlRequests;
use App\Filament\Resources\WidthrawlRequests\Schemas\WidthrawlRequestForm;
use App\Filament\Resources\WidthrawlRequests\Tables\WidthrawlRequestsTable;
use App\Models\WithdrawalRequest;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class WidthrawlRequestResource extends Resource
{
    protected static ?string $model = WithdrawalRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

   
    protected static ?string $recordTitleAttribute = 'investor_name';
    
    protected static string|UnitEnum|null $navigationGroup = 'Wallet Management';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return WidthrawlRequestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WidthrawlRequestsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWidthrawlRequests::route('/'),
            'create' => CreateWidthrawlRequest::route('/create'),
            'edit' => EditWidthrawlRequest::route('/{record}/edit'),
        ];
    }
    
    public static function canViewAny(): bool
    {
        $user = Auth::user();
        return $user && $user->role === 'Agency Owner';
    }
    
    public static function getNavigationItems(): array
{
    $isGrace = Auth::user()?->getSubscriptionState() === 'expired_grace';

    if (!$isGrace) {
        return parent::getNavigationItems();
    }

    return [
        \Filament\Navigation\NavigationItem::make(static::getNavigationLabel())
            ->icon(static::$navigationIcon)
            ->url("javascript: window.dispatchEvent(new CustomEvent('grace-locked'))")
            ->sort(static::getNavigationSort())
            ->badge('🔒'),
    ];
}

    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();
        
        if (!$user) {
            return false;
        }
        
        // Hide from Super Admin, show only for Agency Owner
        return $user->role === 'Agency Owner';
    }
}
