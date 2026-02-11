<?php

namespace App\Filament\Resources\Contacts;

use BackedEnum;
use App\Models\Contact;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Log;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Columns\TextColumn;
use App\Filament\Resources\Contacts\Pages\EditContacts;
use App\Filament\Resources\Contacts\Pages\ListContacts;
use App\Filament\Resources\Contacts\Pages\CreateContacts;
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
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContacts::route('/'),
            'create' => CreateContacts::route('/create'),
            'edit' => EditContacts::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->forUser();
    }
}
