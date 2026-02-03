<?php

namespace App\Filament\Resources\Reviews\Star;

use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\ViewField;
use Illuminate\Support\HtmlString;

class StarColumnHelper
{
    /**
     * Create a TextColumn with star rating display
     */
    public static function makeStarRating(string $name, string $label = null): TextColumn
    {
        return TextColumn::make($name)
            ->label($label ?? ucfirst($name))
            ->alignCenter()
            ->formatStateUsing(function ($state) {
                if (!$state) {
                    $state = 0;
                }
                
                $maxRating = 5;
                $output = '';
                
                // Determine color based on rating
                $starColor = match (true) {
                    $state >= 5 => '#22c55e',
                    $state >= 4 => '#4b5563',
                    $state < 4 => '#fbc83cff',
                    default => '#6b7280',
                };
                
                // Build stars
                for ($i = 1; $i <= $maxRating; $i++) {
                    if ($i <= $state) {
                        $output .= '★';
                    } else {
                        $output .= '☆';
                    }
                }
                
                return $output . ' (' . $state . '/5)';
            })
            ->color(fn ($state) => match (true) {
                $state >= 5 => 'success',
                $state >= 4 => 'gray',
                $state < 4 => 'warning',
                default => 'gray',
            });
    }

    /**
     * Create a Field for star rating input in forms with clickable stars
     */
    public static function makeStarRatingField(string $name, string $label = null): ViewField
    {
        return ViewField::make($name)
            ->label($label ?? 'Rating')
            ->view('filament.forms.components.star-rating')
            ->required()
            ->default(5);
    }
}