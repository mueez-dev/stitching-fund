<?php

namespace App\Filament\Resources\Wallet\Tables;

use Filament\Tables;
use Filament\Tables\Table;
use App\Models\Wallet;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class WalletTable
{
    public static function configure(Table $table): Table
    {
        return self::table($table);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->contentGrid([
                'md' => 2,
                'lg' => 3,
                'xl' => 4,
            ])
            ->columns([
                Tables\Columns\TextColumn::make('wallet_card')
                    ->label('')
                    ->formatStateUsing(function (Wallet $record): string {
                        $availableBalance = $record->available_balance;
                        $totalInvested = $record->active_invested;
                        
                        // Wallet status calculation
                        $walletStatus = ['status' => 'healthy'];
                        
                        if ($availableBalance == 0) {
                            $walletStatus['status'] = 'empty';
                        } elseif ($availableBalance < 50000) {
                            $walletStatus['status'] = 'low';
                        }
                        
                        // Determine card color based on status
                        $bgColor = match($walletStatus['status']) {
                            'empty' => 'bg-gradient-to-br from-red-500 to-red-600',
                            'low' => 'bg-gradient-to-br from-yellow-500 to-orange-500',
                            'healthy' => 'bg-gradient-to-br from-green-500 to-emerald-600',
                            default => 'bg-gradient-to-br from-blue-500 to-indigo-600'
                        };

                        // Safe date formatting
                        $lastDepositDate = 'Unknown';
                        try {
                            if ($record->deposited_at) {
                                $lastDepositDate = $record->deposited_at->format('M d, Y');
                            }
                        } catch (\Exception $e) {
                            $lastDepositDate = 'Recent';
                        }

                        $investorName = e($record->investor->name ?? 'Unknown Investor');
                        $agencyName = e($record->agencyOwner->name ?? 'Unknown Agency');
                        $slipType = e($record->slip_type ? ucfirst(str_replace('_', ' ', $record->slip_type)) : 'Bank');

                        $cardHtml = '
                            <div class="' . $bgColor . ' rounded-xl p-4 sm:p-5 text-white shadow-lg hover:shadow-xl transition-all duration-300 h-full">
                                <!-- Header -->
                                <div class="flex justify-between items-start mb-4 gap-2">
                                    <div class="min-w-0 flex-1">
                                        <h3 class="text-base sm:text-lg font-bold mb-1 truncate">' . $investorName . '</h3>
                                        <p class="text-xs sm:text-sm opacity-90 truncate">' . $agencyName . '</p>
                                    </div>
                                    <div class="bg-white/20 px-2.5 py-1 rounded-full text-xs font-semibold whitespace-nowrap shrink-0">
                                        ' . ucfirst($walletStatus['status']) . '
                                    </div>
                                </div>
                                
                                <!-- Balance Display -->
                                <div class="bg-white/10 backdrop-blur-sm rounded-lg p-3 sm:p-4 mb-4">
                                    <div class="text-xs sm:text-sm opacity-90 mb-1">Available Balance</div>
                                    <div class="text-2xl sm:text-3xl font-bold mb-1 break-all">PKR ' . number_format($availableBalance) . '</div>
                                    <div class="text-xs opacity-80">+12.5% this month</div>
                                </div>
                                
                                <!-- Summary Stats -->
                                <div class="grid grid-cols-3 gap-2 mb-4">
                                    <div class="bg-white/10 rounded-lg p-2">
                                        <div class="text-[10px] sm:text-xs opacity-80 mb-1 leading-tight">Deposited</div>
                                        <div class="text-xs sm:text-sm font-semibold truncate">PKR ' . number_format($record->total_deposits, 0) . '</div>
                                    </div>
                                    <div class="bg-white/10 rounded-lg p-2">
                                        <div class="text-[10px] sm:text-xs opacity-80 mb-1 leading-tight">Invested</div>
                                        <div class="text-xs sm:text-sm font-semibold truncate">PKR ' . number_format($totalInvested, 0) . '</div>
                                    </div>
                                    <div class="bg-white/10 rounded-lg p-2">
                                        <div class="text-[10px] sm:text-xs opacity-80 mb-1 leading-tight">Available</div>
                                        <div class="text-xs sm:text-sm font-semibold truncate">PKR ' . number_format($availableBalance, 0) . '</div>
                                    </div>
                                </div>
                                
                                <!-- Status Message -->';

                        if ($availableBalance == 0) {
                            $cardHtml .= '
                                    <div class="bg-red-500/20 border border-red-400/30 rounded-lg p-2.5 text-center mb-4">
                                        <div class="text-xs font-semibold">Wallet Empty</div>
                                        <div class="text-[10px] sm:text-xs opacity-80 mt-1">No funds available</div>
                                    </div>';
                        } elseif ($availableBalance < 50000) {
                            $cardHtml .= '
                                    <div class="bg-yellow-500/20 border border-yellow-400/30 rounded-lg p-2.5 text-center mb-4">
                                        <div class="text-xs font-semibold">Low Balance</div>
                                        <div class="text-[10px] sm:text-xs opacity-80 mt-1">Consider adding funds</div>
                                    </div>';
                        } else {
                            $cardHtml .= '
                                    <div class="bg-green-500/20 border border-green-400/30 rounded-lg p-2.5 text-center mb-4">
                                        <div class="text-xs font-semibold">Healthy Balance</div>
                                        <div class="text-[10px] sm:text-xs opacity-80 mt-1">Ready for investments</div>
                                    </div>';
                        }
                        
                        $cardHtml .= '
                                
                                <!-- Footer Info -->
                                <div class="flex justify-between items-center pt-3 border-t border-white/20 gap-2">
                                    <div class="text-xs opacity-80 truncate flex-1">
                                        ' . $lastDepositDate . '
                                    </div>
                                    <div class="text-xs opacity-80 whitespace-nowrap shrink-0">
                                        ' . $slipType . '
                                    </div>
                                </div>
                            </div>';
                        
                        return new HtmlString($cardHtml);
                    })
                    ->searchable(false)
                    ->sortable(false),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('wallet_status')
                    ->label('Wallet Status')
                    ->options([
                        'healthy' => 'Healthy Balance',
                        'low' => 'Low Balance', 
                        'empty' => 'Empty Wallet',
                    ])
                    ->query(function ($query, $data) {
                        if ($data['value'] === 'healthy') {
                            return $query->whereRaw('(amount - (SELECT COALESCE(SUM(amount), 0) FROM wallet_allocations WHERE investor_id = wallets.investor_id)) >= 50000');
                        } elseif ($data['value'] === 'low') {
                            return $query->whereRaw('(amount - (SELECT COALESCE(SUM(amount), 0) FROM wallet_allocations WHERE investor_id = wallets.investor_id)) > 0 AND (amount - (SELECT COALESCE(SUM(amount), 0) FROM wallet_allocations WHERE investor_id = wallets.investor_id)) < 50000');
                        } elseif ($data['value'] === 'empty') {
                            return $query->whereRaw('(amount - (SELECT COALESCE(SUM(amount), 0) FROM wallet_allocations WHERE investor_id = wallets.investor_id)) = 0');
                        }
                    }),

                Tables\Filters\SelectFilter::make('investor_id')
                    ->label('Investor')
                    ->relationship('investor', 'name')
                    ->searchable()
                    ->visible(fn () => Auth::user()?->role !== 'Investor'),

                Tables\Filters\SelectFilter::make('slip_type')
                    ->label('Payment Type')
                    ->options([
                        'bank_transfer' => 'Bank Transfer',
                        'cash' => 'Cash',
                        'check' => 'Check',
                    ]),

                Tables\Filters\Filter::make('deposited_today')
                    ->label('Deposited Today')
                    ->query(fn ($query) => $query->whereDate('deposited_at', today())),

                Tables\Filters\Filter::make('deposited_this_week')
                    ->label('This Week')
                    ->query(fn ($query) => $query->whereBetween('deposited_at', [now()->startOfWeek(), now()->endOfWeek()])),
            ])
            ->actions([
                ViewAction::make()
                    ->label('View Details')
                    ->icon('heroicon-o-eye'),
                Action::make('view_slip')
                    ->label('View Slip')
                    ->icon('heroicon-o-document-text')
                    ->url(fn (Wallet $record): string => $record->slip_path ? asset('storage/' . $record->slip_path) : '#')
                    ->openUrlInNewTab()
                    ->visible(fn (Wallet $record): bool => !empty($record->slip_path)),
                EditAction::make()
                    ->label('Edit')
                    ->icon('heroicon-o-pencil')
                    ->visible(fn () => Auth::user()?->role !== 'Investor'),
                DeleteAction::make()
                    ->label('Delete')
                    ->icon('heroicon-o-trash')
                    ->visible(fn () => Auth::user()?->role !== 'Investor'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => Auth::user()?->role !== 'Investor'),
                ]),
            ])
            ->emptyStateActions([
                \Filament\Actions\CreateAction::make()
                    ->label('Create Wallet')
                    ->visible(fn () => Auth::user()?->role !== 'Investor'),
            ])
            ->emptyStateHeading('No wallet deposits found')
            ->emptyStateDescription('Create your first wallet deposit to get started.')
            ->emptyStateIcon('heroicon-o-credit-card')
            ->poll(10);
    }
}