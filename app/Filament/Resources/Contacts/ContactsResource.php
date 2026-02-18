<?php

namespace App\Filament\Resources\Contacts;

use BackedEnum;
use App\Models\Contact;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Resources\Contacts\Pages\EditContacts;    // ✅ Contact pages
use App\Filament\Resources\Contacts\Pages\ListContacts;    // ✅ Contact pages
use App\Filament\Resources\Contacts\Pages\CreateContacts;  // ✅ Contact pages
use App\Filament\Resources\Contacts\Schemas\ContactsForm;
use App\Filament\Resources\Contacts\Tables\ContactsTable;

class ContactsResource extends Resource
{
    protected static ?string $model = Contact::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'contact';

    public static function canViewAny(): bool
    {
        $user = Auth::user();
        return $user && in_array($user->role, ['Super Admin', 'Agency Owner']);
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();
        return $user && $user->role === 'Agency Owner';
    }

    public static function canCreate(): bool
    {
        if (!Auth::check()) return false;
        return Auth::user()?->getSubscriptionState() !== 'expired_grace';
    }

    public static function canEdit(Model $record): bool
    {
        if (!Auth::check()) return false;
        return Auth::user()?->getSubscriptionState() !== 'expired_grace';
    }

    public static function canDelete(Model $record): bool
    {
        if (!Auth::check()) return false;
        return Auth::user()?->getSubscriptionState() !== 'expired_grace';
    }

    public static function getPages(): array
    {
        $isGrace = Auth::check() && Auth::user()?->getSubscriptionState() === 'expired_grace';

        if ($isGrace) {
            return [
                'index' => ListContacts::route('/'),  // ✅ Contacts
            ];
        }

        return [
            'index'  => ListContacts::route('/'),     // ✅ Contacts
            'create' => CreateContacts::route('/create'),
            'edit'   => EditContacts::route('/{record}/edit'),
        ];
    }

    public static function getNavigationItems(): array
    {
        /** @var User $user */
        $user = Auth::user();
        $subscriptionState = $user?->getSubscriptionState();

        if ($subscriptionState === 'locked') {
            return [
                \Filament\Navigation\NavigationItem::make(static::getNavigationLabel())
                    ->icon(static::$navigationIcon)
                    ->url("javascript: window.dispatchEvent(new CustomEvent('account-locked'))")
                    ->sort(static::getNavigationSort())
                    ->badge('🔒'),
            ];
        }

        return parent::getNavigationItems();
    }

    public static function form(Schema $schema): Schema
    {
        return ContactsForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ContactsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->forUser();
    }
}