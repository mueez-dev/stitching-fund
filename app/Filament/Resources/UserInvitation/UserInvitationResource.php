<?php

namespace App\Filament\Resources\UserInvitation;

use BackedEnum;
use Filament\Forms;
use App\Models\User;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use App\Models\UserInvitation;
use Illuminate\Support\Carbon;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Actions\DeleteAction;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use App\Filament\Resources\UserInvitation\Schemas\UserInvitationForm;
use App\Filament\Resources\UserInvitation\Tables\UserInvitationTable;

class UserInvitationResource extends Resource
{
    public static function canViewAny(): bool
    {
        $user = Auth::user();
        return $user && in_array($user->role, ['Super Admin', 'Agency Owner']);
    }

    public static function getNavigationUrl(): string
    {
        return Auth::user()?->getSubscriptionState() === 'expired_grace'
            ? '#'                          // Click hoga, page nahi khulay ga
            : parent::getNavigationUrl();  // Normal URL
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();
        
        if (!$user) {
            return false;
        }
        
        // Always show navigation, even during grace period (but with lock icon)
        return $user && $user->role === 'Agency Owner';
    }

    protected static ?string $model = UserInvitation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Envelope;

    protected static ?string $navigationLabel = 'Investor Invitations';

    protected static ?string $modelLabel = 'Investor Invitation';

    protected static ?string $pluralModelLabel = 'Investor Invitations';
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
            ->group(static::getNavigationGroup())
            ->badge('🔒'),
    ];
}
    
    public static function canCreate(): bool
    {
        // Only allow non-Investor roles to create invitations
        return Auth::user()?->role !== 'Investor';
    }
   
    public static function form(Schema $schema): Schema
    {
        return UserInvitationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UserInvitationTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }
    
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->where('invited_by', Auth::id())
            ->latest();
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\UserInvitation\Pages\ListUserInvitations::route('/'),
            'create' => \App\Filament\Resources\UserInvitation\Pages\CreateUserInvitation::route('/create'),
            'edit' => \App\Filament\Resources\UserInvitation\Pages\EditUserInvitation::route('/{record}/edit'),
        ];
    }

}
