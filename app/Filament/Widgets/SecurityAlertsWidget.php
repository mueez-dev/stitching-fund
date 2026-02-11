<?php

namespace App\Filament\Widgets;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SecurityAlertsWidget extends StatsOverviewWidget
{
    protected ?string $pollingInterval = '30s';

    public static function canView(): bool
    {
        return Auth::user()?->role === 'Super Admin';
    }

    protected function getStats(): array
    {
        // Active sessions in last 30 minutes with error handling
        $activeSessions = 0;
        try {
            if (DB::getSchemaBuilder()->hasTable('sessions')) {
                $activeSessions = DB::table('sessions')
                    ->where('last_activity', '>=', now()->subMinutes(30)->timestamp)
                    ->count();
            }
        } catch (\Exception $e) {
            // Keep default value
        }

       // Users who have registered but don't have active subscription
        $pendingSubscription = DB::table('users')
            ->where(function($query) {
                $query->whereNull('subscription_status')
                      ->orWhere('subscription_status', '!=', 'active')
                      ->orWhere(function($subQuery) {
                          $subQuery->where('subscription_status', 'active')
                                   ->where(function($expQuery) {
                                       $expQuery->whereNull('subscription_expires_at')
                                               ->orWhere('subscription_expires_at', '<=', now());
                                   });
                      });
            })
            ->where('status', 'active')
            ->count();

        return [
            Stat::make('Active Sessions', $activeSessions)
                ->description('Last 30 minutes')
                ->descriptionIcon('heroicon-m-finger-print')
                ->color($activeSessions > 50 ? 'warning' : 'success'),

            Stat::make('Pending Registration', $pendingSubscription)
                ->description('Users need subscription')
                ->descriptionIcon('heroicon-m-credit-card')
                ->color($pendingSubscription > 0 ? 'warning' : 'success'),

            Stat::make('Security Status', 'Monitoring')
                ->description('Live system security')
                ->descriptionIcon('heroicon-m-shield-check')
                ->color('info'),
        ];
    }

    protected function getColumns(): int
    {
        return 4;
    }
}
