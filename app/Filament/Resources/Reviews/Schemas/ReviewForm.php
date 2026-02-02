<?php

namespace App\Filament\Resources\Reviews\Schemas;

use Filament\Schemas\Schema;
use App\Filament\Resources\Reviews\Star\StarColumnHelper;
use Filament\Forms\Components\Field;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;

class ReviewForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('user_name')
                    ->label('User Name')
                    ->default(fn () => Auth::user()?->name)
                    ->disabled()
                    ->required(),
                Hidden::make('user_id')
                    ->default(fn () => Auth::id()),
                Textarea::make('review_text')
                    ->required()
                    ->columnSpanFull()
                    ->rows(8)
                    ->maxLength(5000)
                    ->helperText('Maximum 5000 characters allowed'),
              

                StarColumnHelper::makeStarRatingField('rating')
                    ->columnSpanFull(),
            ]);

    }
}
