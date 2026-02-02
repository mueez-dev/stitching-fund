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
            ->html()
            ->alignCenter()
            ->formatStateUsing(function ($state) {
                return self::renderStars($state);
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

    /**
     * Render star rating HTML with color coding for table display
     */
    private static function renderStars($rating): HtmlString
    {
        if (!$rating) {
            $rating = 0;
        }
        
        $maxRating = 5;
        $stars = '';
        
        // Determine color based on rating
        $starColor = match (true) {
            $rating >= 5 => '#22c55e',      // Success green (5 stars)
            $rating >= 4 => '#4b5563',      // Gray (4 stars)
            $rating < 4 => '#fb923c',       // Warning orange (less than 4)
            default => '#d1d5db',
        };
        
        for ($i = 1; $i <= $maxRating; $i++) {
            if ($i <= $rating) {
                // Filled star
                $stars .= '<svg xmlns="http://www.w3.org/2000/svg" style="width: 18px; height: 18px; display: inline-block; color: ' . $starColor . '; vertical-align: middle; margin: 0 1px;" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                </svg>';
            } else {
                // Empty star
                $stars .= '<svg xmlns="http://www.w3.org/2000/svg" style="width: 18px; height: 18px; display: inline-block; color: #d1d5db; vertical-align: middle; margin: 0 1px;" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                </svg>';
            }
        }
        
        // Add rating number beside stars with smaller font
        $stars .= '<span style="margin-left: 6px; font-size: 12px; font-weight: 500; color: ' . $starColor . '; vertical-align: middle;">(' . $rating . '/5)</span>';
        
        return new HtmlString('<div style="display: inline-flex; align-items: center; justify-content: center;">' . $stars . '</div>');
    }
}