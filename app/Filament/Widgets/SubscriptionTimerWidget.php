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
               $user->role === 'Agency Owner' && 
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
        $graceEndsAt = $user->getGraceEndsAt();
        $subscriptionState = $user->getSubscriptionState();
        
        // Calculate time based on subscription state
        $isExpired = $expiresAt->isPast();
        $isInGracePeriod = $user->isInGracePeriod();
        $isLocked = $user->isLocked();
        
        if ($isLocked) {
            $daysRemaining = 0;
            $hoursRemaining = 0;
            $minutesRemaining = 0;
            $secondsRemaining = 0;
            $timeRemaining = 'LOCKED';
        } elseif ($isInGracePeriod) {
            // Show grace period countdown
            $totalSeconds = $graceEndsAt->timestamp - $now->timestamp;
            $daysRemaining = intval($totalSeconds / 86400);
            $hoursRemaining = intval(($totalSeconds % 86400) / 3600);
            $minutesRemaining = intval(($totalSeconds % 3600) / 60);
            $secondsRemaining = $totalSeconds % 60;
            $timeRemaining = sprintf('GRACE: %dD %02d:%02d:%02d', $daysRemaining, $hoursRemaining, $minutesRemaining, $secondsRemaining);
        } elseif ($isExpired) {
            $daysRemaining = 0;
            $hoursRemaining = 0;
            $minutesRemaining = 0;
            $secondsRemaining = 0;
            $timeRemaining = 'EXPIRED';
        } else {
            // Active subscription - show time until expiry
            $totalSeconds = $expiresAt->timestamp - $now->timestamp;
            $daysRemaining = intval($totalSeconds / 86400);
            $hoursRemaining = intval(($totalSeconds % 86400) / 3600);
            $minutesRemaining = intval(($totalSeconds % 3600) / 60);
            $secondsRemaining = $totalSeconds % 60;
            $timeRemaining = sprintf('%d Days %02d:%02d:%02d', $daysRemaining, $hoursRemaining, $minutesRemaining, $secondsRemaining);
        }
        
        // Determine color and icon based on subscription state
        $color = 'info';
        $icon = 'heroicon-m-check-circle';
        $description = 'Expires At';
        $title = 'Expires On';
        $dateValue = $expiresAt->format('M j, Y H:i');
        $dateDescription = 'Subscription end date';
        
        if ($isLocked) {
            $color = 'gray';
            $icon = 'heroicon-m-lock-closed';
            $description = 'Account Locked';
        } elseif ($isInGracePeriod) {
            $color = 'warning';
            $icon = 'heroicon-m-clock';
            $description = 'Grace Period Ending';
            $title = 'Grace Period Ends';
            $dateValue = $graceEndsAt->format('M j, Y H:i');
            $dateDescription = 'Grace period deadline';
        } elseif ($isExpired) {
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
        
        $stats = [
            Stat::make('Time Remaining', $timeRemaining)
                ->description($description)
                ->descriptionIcon($icon)
                ->color($color),
                
            Stat::make($title, $dateValue)
                ->description($dateDescription)
                ->descriptionIcon('heroicon-m-calendar')
                ->color($isInGracePeriod ? 'warning' : 'info')
        ];
        
        // Fix status display based on subscription state
        $statusValue = match($subscriptionState) {
            'active' => 'Active',
            'expiring' => 'Expiring Soon',
            'expired_grace' => 'Expired (Grace)',
            'locked' => 'Account Locked',
            default => ucfirst($user->subscription_status->value ?? $user->subscription_status)
        };
        
        $statusColor = match($subscriptionState) {
            'active' => 'success',
            'expiring' => 'warning',
            'expired_grace' => 'warning',
            'locked' => 'gray',
            default => $user->subscription_status === 'active' ? 'success' : 'warning'
        };
        
        $statusIcon = match($subscriptionState) {
            'active' => 'heroicon-m-shield-check',
            'expiring' => 'heroicon-m-exclamation-triangle',
            'expired_grace' => 'heroicon-m-clock',
            'locked' => 'heroicon-m-lock-closed',
            default => $user->subscription_status === 'active' ? 'heroicon-m-shield-check' : 'heroicon-m-shield-exclamation'
        };
        
        $stats[] = Stat::make('Status', $statusValue)
            ->description('Subscription status')
            ->descriptionIcon($statusIcon)
            ->color($statusColor);
            
        // Show renew button only when subscription is expiring soon (7 days or less) or expired
        $daysUntilExpiry = $user->subscription_expires_at ? now()->diffInDays($user->subscription_expires_at, false) : 0;
        
        if ($daysUntilExpiry <= 7 && $daysUntilExpiry >= 0) {
            // Expiring soon - show renew button
            $stats[] = Stat::make('Renew Now', 'Extend Subscription')
                ->description('Only ' . $daysUntilExpiry . ' days left')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('warning')
                ->url(route('filament.admin.pages.billing'))
                ->openUrlInNewTab(false)
                ->extraAttributes([
                    'style' => 'cursor: pointer; color: white; padding: 8px 16px; border-radius: 6px; border: none; font-weight: 600;',
                ]);
        } elseif ($daysUntilExpiry < 0) {
            // Expired - show renew button
            $stats[] = Stat::make('Renew Now', 'Subscription Expired')
                ->description('Renew to restore access')
                ->descriptionIcon('heroicon-m-exclamation-circle')
                ->color('danger')
                ->url(route('filament.admin.pages.billing'))
                ->openUrlInNewTab(false)
                ->extraAttributes([
                    'style' => 'cursor: pointer; color: white; padding: 8px 16px; border-radius: 6px; border: none; font-weight: 600;',
                ]);
        }
        
        return $stats;
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
        
        $subscriptionState = $user->getSubscriptionState();
        $daysRemaining = $user->subscription_expires_at->diffInDays(now());
        
        if ($subscriptionState === 'locked') {
            return '<div class="text-center text-sm text-gray-600 font-medium">
                <strong>Account Locked:</strong> Your subscription has expired and grace period ended. Please renew to restore access.
            </div>';
        }
        
        if ($subscriptionState === 'expired_grace') {
            $graceEndsAt = $user->getGraceEndsAt();
            $now = Carbon::now();
            $totalGraceSeconds = $graceEndsAt->timestamp - $now->timestamp;
            
            if ($totalGraceSeconds > 0) {
                $graceDaysRemaining = intval($totalGraceSeconds / 86400);
                $graceHoursRemaining = intval(($totalGraceSeconds % 86400) / 3600);
                
                if ($graceDaysRemaining > 0) {
                    $timeText = $graceDaysRemaining . ' day(s) left';
                } else {
                    $timeText = $graceHoursRemaining . ' hour(s) left';
                }
            } else {
                $timeText = 'ending soon';
            }
            
            return '<div class="text-center text-sm text-warning-600 font-medium">
                <strong>Grace Period:</strong> You have ' . $timeText . ' to renew. Limited access available.
            </div>';
        }
        
        if ($daysRemaining <= 3 && $daysRemaining >= 0) {
            return '<div class="text-center text-sm text-danger-600 font-medium">
                <strong>Action Required:</strong> Your subscription expires soon. Please renew to avoid service interruption.
            </div>';
        }
        
        return null;
    }
}
