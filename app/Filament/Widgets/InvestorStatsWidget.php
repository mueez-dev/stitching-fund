<?php

namespace App\Filament\Widgets;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;





class InvestorStatsWidget extends StatsOverviewWidget
{
    protected ?string $pollingInterval = '30s';
    
    public static function canView(): bool
    {
        return Auth::user()?->role === 'Investor';
    }
    
    protected function getStats(): array
    {
        $user = Auth::user();
        
        // Get wallet data with proper error handling
        $walletData = DB::table('wallets')
            ->where('investor_id', $user->id)
            ->first();
        
        $walletAmount = $walletData->available_balance ?? 0;
        $totalDeposits = $walletData->total_deposits ?? 0;
        
        // Calculate total invested from LATs
        $totalInvested = DB::table('lats')
            ->where('user_id', $user->id)
            ->where('payment_status', 'complete')
            ->sum('initial_investment') ?? 0;
        
        // Calculate pending payments
        $pendingPayments = DB::table('lats')
            ->where('user_id', $user->id)
            ->where('payment_status', 'pending')
            ->sum('initial_investment') ?? 0;
        
        // Count completed investments
        $completedPayments = DB::table('lats')
            ->where('user_id', $user->id)
            ->where('payment_status', 'complete')
            ->count();
        
        return [
            Stat::make('Available Balance', number_format($walletAmount, 0))
                ->description('Current available balance')
                ->descriptionIcon('heroicon-m-wallet')
                ->color('primary'),
                
            Stat::make('Total Invested', number_format($totalInvested, 0))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
                
            Stat::make('Pending Payments', number_format($pendingPayments, 0))
                ->description('Awaiting payment')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pendingPayments > 0 ? 'warning' : 'success'),
                
            Stat::make('Total Deposits', number_format($totalDeposits, 0))
                ->description('Total amount deposited')
                ->descriptionIcon('heroicon-m-arrow-down-circle')
                ->color('info'),    
        ];
    }
    
    protected function getColumns(): int
    {
        return 4;
    }
}