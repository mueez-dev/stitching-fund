<?php

namespace App\Filament\Resources\Lats;

use BackedEnum;
use App\Models\Lat;
use Filament\Tables\Table;
use App\Models\LatMaterial;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Log;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use App\Models\Scopes\AgencyOwnerScope;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\Lats\Pages\EditLats;
use App\Filament\Resources\Lats\Pages\ListLats;
use App\Filament\Resources\Lats\Pages\CreateLats;     
use App\Filament\Resources\Lats\Pages\LatDetails;
use App\Filament\Resources\Lats\Schemas\LatsForm;
use App\Filament\Resources\Lats\Tables\LatsTable;

class LatsResource extends Resource
{
    protected static ?string $model = Lat::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

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

    protected static ?string $navigationLabel = 'Lots';
    protected static ?string $modelLabel = 'Lot';
    protected static ?string $pluralModelLabel = 'Lots';
    protected static ?string $recordTitleAttribute = 'lot_no';

    public static function form(Schema $schema): Schema
    {
        return LatsForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LatsTable::configure($table);
    }
     public static function canCreate(): bool
    {
        return Auth::check(); // only logged-in can create
    }

    public static function canEdit($record): bool
    {
        return $record && $record->canBeManagedBy();
    }

    public static function canDelete($record): bool
    {
        return $record && $record->canBeManagedBy();
    }

   

   public static function getPages(): array
    {
        return [
            'index' => Pages\ListLats::route('/'),
            'create' => Pages\CreateLats::route('/create'),
            'view' => Pages\LatDetails::route('/{record}'),
            'edit' => Pages\EditLats::route('/{record}/edit'),
        ];
    }

     public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->forUser();
    }

   
     public static function getRelations(): array
    {
        return [
            RelationManagers\MaterialsRelationManager::class,
            RelationManagers\ExpenseRelationManager::class,
            RelationManagers\SummaryRelationManager::class,
            RelationManagers\InvestmentPoolRelationManager::class,
            // RelationManagers\PaymentsRelationManager::class,
        ];
    }
   
}