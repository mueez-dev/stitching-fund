<?php

namespace App\Filament\Widgets;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class SubscriptionTimerWidget extends StatsOverviewWidget
{
    protected ?string $pollingInterval = '1s';
    
    protected static ?int $sort = 1;
    
    public static function canView(): bool
    {
        $user = Auth::user();
        return $user && 
               ($user->role === 'Agency Owner' || $user->role === 'Super Admin') && 
               $user->subscription_expires_at;
    }
    
    protected function getStats(): array
    {
        $user = Auth::user();
        
        if (!$user || !$user->subscription_expires_at) {
            return [];
        }
        
        $expiresAt = $user->subscription_expires_at;
        $now = Carbon::now();
        
        // Calculate if expired and remaining time
        $isExpired = $expiresAt->isPast();
        
        if ($isExpired) {
            $daysRemaining = 0;
            $hoursRemaining = 0;
            $minutesRemaining = 0;
            $secondsRemaining = 0;
        } else {
            // Use absolute difference to ensure positive values
            $totalSeconds = $expiresAt->timestamp - $now->timestamp;
            $daysRemaining = intval($totalSeconds / 86400); // 86400 seconds in a day
            $hoursRemaining = intval(($totalSeconds % 86400) / 3600);
            $minutesRemaining = intval(($totalSeconds % 3600) / 60);
            $secondsRemaining = $totalSeconds % 60;
            
        }
        
        // Determine color based on urgency
        $color = 'info';
        $icon = 'heroicon-m-check-circle';
        $description = 'Expires At';
        
        if ($isExpired) {
            $color = 'danger';
            $icon = 'heroicon-m-x-circle';
            $description = 'Subscription Expired';
        } elseif ($daysRemaining <= 3) {
            $color = 'danger';
            $icon = 'heroicon-m-exclamation-triangle';
            $description = 'Expires Soon!';
        } elseif ($daysRemaining <= 7) {
            $color = 'warning';
            $icon = 'heroicon-m-clock';
            $description = 'Expires This Week';
        }
        
        $timeRemaining = $isExpired ? 'EXPIRED' : sprintf('%d Days %02d:%02d:%02d', $daysRemaining, $hoursRemaining, $minutesRemaining, $secondsRemaining);
        
        return [
            Stat::make('Time Remaining', $timeRemaining)
                ->description($description)
                ->descriptionIcon($icon)
                ->color($color),
                
            Stat::make('Expires On', $expiresAt->format('M j, Y H:i'))
                ->description('Subscription end date')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('info'),
                
            Stat::make('Status', ucfirst($user->subscription_status))
                ->description('Subscription status')
                ->descriptionIcon($user->subscription_status === 'active' ? 'heroicon-m-shield-check' : 'heroicon-m-shield-exclamation')
                ->color($user->subscription_status === 'active' ? 'success' : 'warning'),
            Stat::make('Renew Subscription', 'Click to Renew')
                ->description('Renew your subscription')
                ->descriptionIcon('heroicon-m-credit-card')
                ->color('primary')
                 ->url('/subscription')
                ->openUrlInNewTab()
                ->extraAttributes([
                    'style' => 'cursor: pointer;  color: white; padding: 8px 16px; border-radius: 6px; border: none; font-weight: 600;',
                ]),
        ];
    }
    
    protected function getColumns(): int
    {
        return 4;
    }
    
    protected function getFooter(): ?string
    {
        $user = Auth::user();
        if (!$user || !$user->subscription_expires_at) {
            return null;
        }
        
        $daysRemaining = $user->subscription_expires_at->diffInDays(now());
        
        if ($daysRemaining <= 3 && $daysRemaining >= 0) {
            return '<div class="text-center text-sm text-danger-600 font-medium">
                <strong>⚠️ Action Required:</strong> Your subscription expires soon. Please renew to avoid service interruption.
            </div>';
        }
        
        return null;
    }
}
