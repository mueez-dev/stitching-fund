<?php

namespace App\Filament\Resources\Wallet;

use UnitEnum;
use BackedEnum;
use App\Models\User;
use Filament\Tables;
use App\Models\Wallet;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Actions\EditAction;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use App\Filament\Resources\Wallet\Schemas\WalletForm;
use App\Filament\Resources\Wallet\Pages\EditWallet;
use App\Filament\Resources\Wallet\Pages\ListWallets;
use App\Filament\Resources\Wallet\Pages\CreateWallet;
use App\Filament\Resources\Wallet\Tables\WalletTable;

class WalletResource extends Resource
{
    protected static ?string $model = Wallet::class;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';
   
    public static function form(Schema $schema): Schema
    {
        return $schema->schema(WalletForm::schema());
    }

    public static function table(Table $table): Table
    {
        return WalletTable::table($table);
    }

    public static function canViewAny(): bool
    {
         $user = Auth::user();
        return $user && in_array($user->role, ['Super Admin', 'Agency Owner', 'Investor']);
    }

    public static function shouldRegisterNavigation(): bool
    {
         $user = Auth::user();
        return $user && in_array($user->role, ['Agency Owner', 'Investor']);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\Wallet\Pages\ListWallets::route('/'),
            'create' => \App\Filament\Resources\Wallet\Pages\CreateWallet::route('/create'),
            'edit' => \App\Filament\Resources\Wallet\Pages\EditWallet::route('/{record}/edit'),
            'transaction-history' => \App\Filament\Resources\Wallet\Pages\TransactionHistory::route('/transaction-history/{walletId?}'),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return 'Wallet';
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();
        
        if (!$user) {
            return $query->whereRaw('1 = 0');
        }
        
        // Super Admin sees all
        if ($user->role === 'Super Admin') {
            return $query;
        }
        
        // Agency Owner sees all wallets from their agency
        if ($user->role === 'Agency Owner') {
            return $query->where('agency_owner_id', $user->id);
        }
        
        // Investor sees all wallets from their agency (same as investment pools)
        if ($user->role === 'Investor') {
            if ($user->invited_by) {
                return $query->where('agency_owner_id', $user->invited_by);
            } else {
                return $query->whereRaw('1 = 0'); // No valid inviter
            }
        }
        
        return $query->whereRaw('1 = 0');
    }
    
    public static function canCreate(): bool
    {
        return Auth::user()->role === 'Agency Owner';
    }
    
    public static function canEdit($record): bool
    {
        $user = Auth::user();
        if (!$user || !$record) return false;
        
        // Super Admin can edit all
        if ($user->role === 'Super Admin') return true;
        
        // Agency Owner can edit wallets from their agency
        if ($user->role === 'Agency Owner' && $record->agency_owner_id === $user->id) return true;
        
        // Investors cannot edit
        return false;
    }
    
    public static function canDelete($record): bool
    {
        $user = Auth::user();
        if (!$user || !$record) return false;
        
        // Super Admin can delete all
        if ($user->role === 'Super Admin') return true;
        
        // Agency Owner can delete wallets from their agency
        if ($user->role === 'Agency Owner' && $record->agency_owner_id === $user->id) return true;
        
        // Investors cannot delete
        return false;
    }
    
    public static function canView($record): bool
    {
        $user = Auth::user();
        if (!$user || !$record) return false;
        
        // Super Admin can view all
        if ($user->role === 'Super Admin') return true;
        
        // Agency Owner can view wallets from their agency
        if ($user->role === 'Agency Owner' && $record->agency_owner_id === $user->id) return true;
        
        // Investor can view wallets from their agency (same as query logic)
        if ($user->role === 'Investor' && $user->invited_by && $record->agency_owner_id === $user->invited_by) return true;
        
        return false;
    }
   public static function getNavigationItems(): array
    {
        /** @var User $user */
        $user = Auth::user();
        $subscriptionState = $user?->getSubscriptionState();
        
        // For locked period, only show dashboard and billing
        if ($subscriptionState === 'locked') {
            return [
                \Filament\Navigation\NavigationItem::make(static::getNavigationLabel())
                    ->icon(static::$navigationIcon)
                    ->url("javascript: window.dispatchEvent(new CustomEvent('account-locked'))")
                    ->sort(static::getNavigationSort())
                    ->badge('🔒'),

               
            ];
        }
        
        // For grace period, show grace locked message
        if ($subscriptionState === 'expired_grace') {
            return [
                \Filament\Navigation\NavigationItem::make(static::getNavigationLabel())
                    ->icon(static::$navigationIcon)
                    ->url("javascript: window.dispatchEvent(new CustomEvent('grace-locked'))")
                    ->sort(static::getNavigationSort())
                    ->badge('🔒'),
            ];
        }

        // Normal access - show all navigation items
        return parent::getNavigationItems();
    }
   
}