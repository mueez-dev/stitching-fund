<?php

namespace App\Filament\Resources\InvestmentPool;

use UnitEnum;
use BackedEnum;
use App\Models\Lat;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use App\Models\InvestmentPool;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Log;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Placeholder;
use App\Filament\Resources\InvestmentPool\Pages\EditInvestmentPool;
use App\Filament\Resources\InvestmentPool\Pages\ViewInvestmentPool;
use App\Filament\Resources\InvestmentPool\Pages\ListInvestmentPools;
use App\Filament\Resources\InvestmentPool\Pages\CreateInvestmentPool;
use App\Filament\Resources\InvestmentPool\Schemas\InvestmentPoolForm;
use App\Filament\Resources\InvestmentPool\Tables\InvestmentPoolTable;

class InvestmentPoolResource extends Resource
{
    protected static ?string $model = InvestmentPool::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Banknotes;
  
    public static function form(Schema $schema): Schema
    {
        $schema = InvestmentPoolForm::configure($schema);
        
        // If we're creating from a LAT, pre-fill the lat_id
        if (request()->has('lat_id')) {
            $components = $schema->getComponents();
            $components[] = \Filament\Forms\Components\Hidden::make('lat_id')
                ->default(request('lat_id'));
            $schema->schema($components);
        }
        
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return InvestmentPoolTable::configure($table);
    }

    
    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInvestmentPools::route('/'),
            'create' => CreateInvestmentPool::route('/create'),
            'view' => ViewInvestmentPool::route('/{record}'),
            'edit' => EditInvestmentPool::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        $user = Auth::user();
        
        if (!$user) {
            return false;
        }
        
        // Additional check for investors - must have a valid inviter
        if ($user->role === 'Investor') {
            $hasValidInviter = $user->invited_by && 
                             \App\Models\User::where('id', $user->invited_by)
                                            ->where('role', 'Agency Owner')
                                            ->exists();
            
          
        }
        
        return in_array($user->role, ['Super Admin', 'Agency Owner', 'Investor']);
    }


 public static function canView($record): bool
{
    $user = Auth::user();
    if (!$user) {
        // No logging - removed UTF-8 corruption
        return false;
    }
    
   
    // Super Admin can view all pools regardless of status
    if ($user->role === 'Super Admin') {
        $canView = true;
        // No logging - removed UTF-8 corruption
        return $canView;
    }
    
    // Agency Owner can view their own pools regardless of status
    if ($user->role === 'Agency Owner' && $record->user_id === $user->id) {
        $canView = true;
        // No logging - removed UTF-8 corruption
        return $canView;
    }
    
    // Investor can view pools from their inviter regardless of status
    if ($user->role === 'Investor' && $user->invited_by === $record->user_id) {
        $canView = true;
        // No logging - removed UTF-8 corruption
        return $canView;
    }
    
    // No logging - removed UTF-8 corruption
    return false;
}
    public static function canEdit($record): bool
    {
        $user = Auth::user();
        if (!$user) return false;
        
        // Super Admin can edit all
        if ($user->role === 'Super Admin') return true;
        
        // Agency Owner can edit their own pools
        if ($user->role === 'Agency Owner' && $record->user_id === $user->id) return true;
        
        // Investors cannot edit
        return false;
    }

    public static function canCreate(): bool
{
    $user = Auth::user();
    if (!$user) return false;
    
    // Super Admin and Agency Owner can create
    return in_array($user->role, ['Super Admin', 'Agency Owner']);
}

    public static function canDelete($record): bool
    {
        $user = Auth::user();
        if (!$user) return false;
        
        // Super Admin can delete all
        if ($user->role === 'Super Admin') return true;
        
        // Agency Owner can delete their own pools
        if ($user->role === 'Agency Owner' && $record->user_id === $user->id) return true;
        
        // Investors cannot delete
        return false;
    }

    public static function getNavigationItems(): array
{
    $isGrace = Auth::user()?->getSubscriptionState() === 'expired_grace';

    if (!$isGrace) {
        return parent::getNavigationItems();
    }

    return [
        \Filament\Navigation\NavigationItem::make(static::getNavigationLabel())
            ->icon(static::$navigationIcon)
            ->url("javascript: window.dispatchEvent(new CustomEvent('grace-locked'))")
            ->sort(static::getNavigationSort())
            ->badge('🔒'),
    ];
}

    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();
        
        // Hide from SuperAdmin, show to Agency Owner and Investor
        if (!$user || !in_array($user->role, ['Agency Owner', 'Investor']) || request()->has('lat_id')) {
            return false;
        }
        
        // Always show navigation, even during grace period (but with lock icon)
        return true;
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();

        $query = parent::getEloquentQuery();

        // If we're viewing from a LAT, only show pools for that LAT
        if (request()->has('lat_id')) {
            $query->where('lat_id', request('lat_id'));
        }

        // Super Admin sees all investment pools
        if ($user->role === 'Super Admin') {
        return $query;
    }

        // Agency Owner sees only their own investment pools
        if ($user->role === 'Agency Owner') {
        return $query->where('user_id', $user->id);
    }

        // Investor sees investment pools from their inviter (Agency Owner)
        if ($user->role === 'Investor') {
            $invitedBy = $user->invited_by;
            
            if ($invitedBy) {
                // Verify the inviter is an Agency Owner
                $inviter = \App\Models\User::find($invitedBy);
                
                if ($inviter && $inviter->role === 'Agency Owner') {
                    return $query->where('user_id', $invitedBy); // Show all pools (open, active, closed)
                } else {
                    Log::warning('Investor has invalid inviter', [
                        'investor_id' => $user->id,
                        'inviter_id' => $invitedBy,
                        'inviter_role' => $inviter ? $inviter->role : 'not_found'
                    ]);
                }
            }
            
            Log::warning('Investor has no valid inviter assigned', ['user_id' => $user->id]);
            return $query->whereNull('id');
        }

        // Default: show nothing for unauthorized roles
    return $query->whereNull('id');
    }

    protected static function handleRecordCreation(array $data): Model
    {
        Log::info('handleRecordCreation called', ['data_keys' => array_keys($data)]);
        
        // Test notification at the very beginning
       Notification::make()
            ->title('Pool Creation Started')
            ->body('Creating investment pool...')
            ->info()
            ->send();
        
        // Check if partners data is in individual fields format
        $partnersData = [];
        if (isset($data['partners']) && is_array($data['partners'])) {
            // Filter out empty partner entries and validate data
            $partnersData = array_filter($data['partners'], function($partner) {
                return !empty($partner['investor_id']) && !empty($partner['investment_amount']);
            });
        } elseif (isset($data['partners']) && is_string($data['partners'])) {
            // Parse JSON string
            $decodedData = json_decode($data['partners'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decodedData)) {
                // Filter out empty entries
                $partnersData = array_filter($decodedData, function($partner) {
                    return !empty($partner['investor_id']) && !empty($partner['investment_amount']);
                });
            } else {
                Log::error('Failed to decode partners JSON', ['json_error' => json_last_error_msg(), 'data' => $data['partners']]);
                $partnersData = [];
            }
        } else {
            // Try to build partners array from individual fields
            $partnersData = [];
            for ($i = 0; $i < 6; $i++) {
                $investorId = $data["partners.{$i}.investor_id"] ?? null;
                $investmentAmount = $data["partners.{$i}.investment_amount"] ?? null;
                $investmentPercentage = $data["partners.{$i}.investment_percentage"] ?? null;
                
                if ($investorId && $investmentAmount) {
                    $partnersData[] = [
                        'investor_id' => $investorId,
                        'investment_amount' => $investmentAmount,
                        'investment_percentage' => $investmentPercentage,
                    ];
                }
            }
        }
        
        Log::info('Processed partners data', ['count' => count($partnersData), 'data' => $partnersData]);
        
     
   
        DB::beginTransaction();
        try {
            // Create the investment pool first (equal distribution is handled in the model)
    
            $record = static::getModel()::create($data);
            
            // Debug: Check if partners data exists
            session()->flash('debug', 'Partners data: ' . (empty($partnersData) ? 'EMPTY' : 'FOUND - ' . count($partnersData) . ' items'));
            
            // Store partners data in JSON column
            if (!empty($partnersData)) {
                Log::info('About to call processWalletAllocations', [
                    'pool_id' => $record->id,
                    'partners_count' => count($partnersData)
                ]);
                
                $record->partners = $partnersData;
                $record->save();
                
                // Process wallet allocations and get results
                $allocationResults = self::processWalletAllocations($record, $partnersData);
                
                Log::info('processWalletAllocations completed', [
                    'success_count' => count($allocationResults['success']),
                    'error_count' => count($allocationResults['errors']),
                    'results' => $allocationResults
                ]);
                
                // Show wallet deduction notification
                if (!empty($allocationResults['success'])) {
                    $successMessage = 'Wallet deductions successful: ' . implode(', ', $allocationResults['success']);
                    session()->flash('success', $successMessage);
                }
                
                if (!empty($allocationResults['errors'])) {
                    $errorMessage = 'Wallet deduction errors: ' . implode(', ', $allocationResults['errors']);
                    session()->flash('error', $errorMessage);
                }
            } else {
                // Test notification for no partners
                session()->flash('warning', 'No partners data was provided');
            }
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
        
        return $record;
    }
    
    protected static function handleRecordUpdate(Model $record, array $data): Model
    {
        Log::info('handleRecordUpdate called', ['pool_id' => $record->id, 'data_keys' => array_keys($data)]);
        
        // Process partners data similar to creation
        $partnersData = [];
        if (isset($data['partners']) && is_array($data['partners'])) {
            $partnersData = array_filter($data['partners'], function($partner) {
                return !empty($partner['investor_id']) && !empty($partner['investment_amount']);
            });
        } else {
            // Try to build partners array from individual fields
            $partnersData = [];
            for ($i = 0; $i < 6; $i++) {
                $investorId = $data["partners.{$i}.investor_id"] ?? null;
                $investmentAmount = $data["partners.{$i}.investment_amount"] ?? null;
                $investmentPercentage = $data["partners.{$i}.investment_percentage"] ?? null;
                
                if ($investorId && $investmentAmount) {
                    $partnersData[] = [
                        'investor_id' => $investorId,
                        'investment_amount' => $investmentAmount,
                        'investment_percentage' => $investmentPercentage,
                    ];
                }
            }
        }
        
        Log::info('Processed partners data for update', ['count' => count($partnersData), 'data' => $partnersData]);
        
        DB::beginTransaction();
        try {
            // Store old partners for comparison
            $oldPartners = $record->partners ?? [];
            $oldPartnerMap = [];
            foreach ($oldPartners as $partner) {
                if (!empty($partner['investor_id'])) {
                    $oldPartnerMap[$partner['investor_id']] = $partner;
                }
            }
            
            // Update the investment pool with new data
            $record->update($data);
            
            // Update partners data
            if (!empty($partnersData)) {
                $record->partners = $partnersData;
                $record->save();
                
                // Process wallet allocation changes
                $allocationResults = self::processWalletAllocationUpdates($record, $partnersData, $oldPartnerMap);
                
                Log::info('processWalletAllocationUpdates completed', [
                    'success_count' => count($allocationResults['success']),
                    'error_count' => count($allocationResults['errors']),
                    'results' => $allocationResults
                ]);
                
                // Show notifications
                if (!empty($allocationResults['success'])) {
                    $successMessage = 'Wallet updates successful: ' . implode(', ', $allocationResults['success']);
                    session()->flash('success', $successMessage);
                }
                
                if (!empty($allocationResults['errors'])) {
                    $errorMessage = 'Wallet update errors: ' . implode(', ', $allocationResults['errors']);
                    session()->flash('error', $errorMessage);
                }
            }
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating investment pool', ['error' => $e->getMessage()]);
            throw $e;
        }
        
        return $record;
    }
    
    /**
     * Process wallet allocation updates for edited investment pool
     */
    private static function processWalletAllocationUpdates($record, $newPartners, $oldPartnerMap)
    {
        $results = [
            'success' => [],
            'errors' => []
        ];
        
        // Get existing allocations for this pool
        $existingAllocations = \App\Models\WalletAllocation::where('investment_pool_id', $record->id)
            ->get()
            ->keyBy('investor_id');
        
        $newPartnerMap = [];
        foreach ($newPartners as $partner) {
            if (!empty($partner['investor_id'])) {
                $newPartnerMap[$partner['investor_id']] = $partner;
            }
        }
        
        // Process updates and new allocations
        foreach ($newPartners as $partner) {
            $investorId = intval($partner['investor_id']);
            $newAmount = floatval($partner['investment_amount']);
            $oldAmount = 0;
            
            // Get old amount if existed
            if (isset($oldPartnerMap[$investorId])) {
                $oldAmount = floatval($oldPartnerMap[$investorId]['investment_amount'] ?? 0);
            }
            
            // Find investor's wallet
            $wallet = \App\Models\Wallet::where('investor_id', $investorId)->first();
            
            if (!$wallet) {
                $results['errors'][] = "Investor {$investorId}: Wallet not found";
                continue;
            }
            
            $existingAllocation = $existingAllocations->get($investorId);
            
            if ($existingAllocation) {
                // Update existing allocation
                $amountDifference = $newAmount - $oldAmount;
                
                if ($amountDifference != 0) {
                    // Check if we need to deduct more or return money
                    if ($amountDifference > 0) {
                        // Need to deduct more
                        if ($wallet->available_balance < $amountDifference) {
                            $results['errors'][] = "Investor {$investorId}: Insufficient funds for additional investment (Available: PKR {$wallet->available_balance}, Required: PKR {$amountDifference})";
                            continue;
                        }
                        
                        // Create additional investment ledger entry
                        \App\Models\WalletLedger::createInvestment($wallet, $amountDifference, $existingAllocation, "Additional investment for pool #{$record->id}");
                        $results['success'][] = "Investor {$investorId}: Additional PKR {$amountDifference} invested";
                    } else {
                        // Need to return money
                        $returnAmount = abs($amountDifference);
                        \App\Models\WalletLedger::createReturn($wallet, $returnAmount, "Investment reduction for pool #{$record->id}");
                        $results['success'][] = "Investor {$investorId}: PKR {$returnAmount} returned to wallet";
                    }
                    
                    // Update allocation amount
                    $existingAllocation->amount = $newAmount;
                    $existingAllocation->save();
                }
            } elseif ($newAmount > 0) {
                // New allocation for this investor
                if ($wallet->available_balance < $newAmount) {
                    $results['errors'][] = "Investor {$investorId}: Insufficient funds (Available: PKR {$wallet->available_balance}, Required: PKR {$newAmount})";
                    continue;
                }
                
                // Create new allocation
                $allocation = \App\Models\WalletAllocation::create([
                    'wallet_id' => $wallet->id,
                    'investor_id' => $investorId,
                    'investment_pool_id' => $record->id,
                    'amount' => $newAmount,
                ]);
                
                // Create investment ledger entry
                \App\Models\WalletLedger::createInvestment($wallet, $newAmount, $allocation, "Investment in pool #{$record->id}");
                
                $results['success'][] = "Investor {$investorId}: PKR {$newAmount} invested";
            }
        }
        
        // Handle removed investors (return their money)
        foreach ($oldPartnerMap as $investorId => $oldPartner) {
            if (!isset($newPartnerMap[$investorId])) {
                $existingAllocation = $existingAllocations->get($investorId);
                
                if ($existingAllocation) {
                    $wallet = \App\Models\Wallet::where('investor_id', $investorId)->first();
                    
                    if ($wallet) {
                        $returnAmount = floatval($existingAllocation->amount);
                        
                        // Return money to wallet
                        \App\Models\WalletLedger::createReturn($wallet, $returnAmount, "Investment cancelled for pool #{$record->id}");
                        
                        // Delete allocation
                        $existingAllocation->delete();
                        
                        $results['success'][] = "Investor {$investorId}: PKR {$returnAmount} returned (investment cancelled)";
                    }
                }
            }
        }
        
        return $results;
    }
    
    /**
     * Process wallet allocations for investment pool partners
     */
    private static function processWalletAllocations($record, $partnersData)
    {
        Log::info('processWalletAllocations called', [
            'pool_id' => $record->id,
            'partners_count' => count($partnersData),
            'partners_data' => $partnersData
        ]);
        
        $results = [
            'success' => [],
            'errors' => []
        ];
        
        foreach ($partnersData as $index => $partner) {
            // Debug: Check partner data
            $partnerInfo = "Partner " . ($index + 1) . ": ID=" . ($partner['investor_id'] ?? 'NULL') . ", Amount=" . ($partner['investment_amount'] ?? 'NULL');
            Log::info('Processing partner', ['partner_info' => $partnerInfo]);
            
            // Convert investor_id to integer and validate
            $investorId = isset($partner['investor_id']) ? intval($partner['investor_id']) : null;
            $investmentAmount = isset($partner['investment_amount']) ? floatval($partner['investment_amount']) : 0;
            
            if (!$investorId || $investmentAmount <= 0) {
                $errorMsg = "Partner " . ($index + 1) . ": Missing or invalid investor ID ({$investorId}) or investment amount ({$investmentAmount})";
                $results['errors'][] = $errorMsg;
                Log::error('Partner validation failed', ['error' => $errorMsg, 'partner' => $partner]);
                continue;
            }
            
            // Find investor's wallet
            $wallet = \App\Models\Wallet::where('investor_id', $investorId)->first();
            
            if ($wallet) {
                // Debug: Wallet found
                $walletInfo = "Wallet found: ID=" . $wallet->id . ", Balance=" . $wallet->amount;
                Log::info('Wallet found', ['wallet_info' => $walletInfo]);
                
                // Check if wallet has sufficient balance
                if ($wallet->amount < $investmentAmount) {
                    $results['errors'][] = "Investor {$investorId}: Insufficient funds (Available: PKR {$wallet->amount}, Required: PKR {$investmentAmount})";
                    continue;
                }
                
                try {
                    // Deduct from wallet
                    $wallet->amount -= $investmentAmount;
                    $wallet->save();
                    
                    // Create wallet allocation
                    $allocation = \App\Models\WalletAllocation::create([
                        'wallet_id' => $wallet->id,
                        'investor_id' => $investorId,
                        'investment_pool_id' => $record->id,
                        'amount' => $investmentAmount,
                    ]);
                    
                    if ($allocation) {
                        $results['success'][] = "Investor {$investorId}: PKR {$investmentAmount} deducted and allocated successfully";
                        Log::info('Wallet allocation created', [
                            'allocation_id' => $allocation->id,
                            'investor_id' => $investorId,
                            'amount' => $investmentAmount,
                            'pool_id' => $record->id
                        ]);
                    } else {
                        $results['errors'][] = "Investor {$investorId}: Failed to create allocation";
                        Log::error('Failed to create allocation', ['investor_id' => $investorId]);
                    }
                } catch (\Exception $e) {
                    $results['errors'][] = "Investor {$investorId}: " . $e->getMessage();
                    Log::error('Exception during wallet allocation', [
                        'investor_id' => $investorId,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            } else {
                $results['errors'][] = "Investor {$investorId}: Wallet not found";
                Log::error('Wallet not found for investor', ['investor_id' => $investorId]);
            }
        }
        
        return $results;
    }
}