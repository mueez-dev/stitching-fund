<?php

namespace App\Filament\Widgets;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;




class SuperAdminStatsWidget extends StatsOverviewWidget
{ 
    protected ?string $pollingInterval = '30s';
    
    public static function canView(): bool
    {
        return Auth::user()?->role === 'Super Admin';
    }
    
    protected function getStats(): array
    {
        $totalUsers = DB::table('users')->count();
        $totalInvestors = DB::table('users')->where('role', 'Investor')->count();
        $totalAgencyOwners = DB::table('users')->where('role', 'Agency Owner')->count();
        
       
            
        // Inactive agencies (Agency Owners with inactive status)
        $inactiveAgencies = DB::table('users')
            ->where('role', 'Agency Owner')
            ->where('status', 'inactive')
            ->count();
            
        // Users awaiting approval
        $pendingApprovals = DB::table('users')
            ->where('status', 'pending')
            ->orWhere('status', 'inactive')
            ->count();
            
        return [    
            Stat::make('Total Users', $totalUsers)
                ->description('All registered users')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),
                
            Stat::make('Investors', $totalInvestors)
                ->description('Active investors')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
                
            Stat::make('Agency Owners', $totalAgencyOwners) 
                ->description('Registered agencies')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('warning'),
         
                
            Stat::make('Inactive Agencies', $inactiveAgencies)
                ->description('Agencies deactivated by admin')
                ->descriptionIcon('heroicon-m-shield-exclamation')
                ->color($inactiveAgencies > 0 ? 'danger' : 'success'),
        ];
    }
    
    protected function getColumns(): int
    {
        return 4;
    }
}