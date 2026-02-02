<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Filament\Notifications\Notification;

class InvestmentPool extends Model
{

    protected $fillable = [
        'lat_id',
        'design_name',
        'amount_required',
        'number_of_partners',
        'total_collected',
        'percentage_collected',
        'remaining_amount',
        'partners',
        'status',
        'user_id',
        'returns_distributed',
        'returns_distributed_at',
    ];

    protected $appends = [
        'is_fully_funded',
    ];

    protected $casts = [
        'amount_required' => 'decimal:2',
        'total_collected' => 'decimal:2',
        'number_of_partners' => 'integer',
        'partners' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'returns_distributed' => 'boolean',
        'returns_distributed_at' => 'datetime',
    ];

    public const STATUS_DRAFT = 'draft';
    public const STATUS_OPEN = 'open';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_FULLY_FUNDED = 'fully_funded';
    public const STATUS_CLOSED = 'closed';

    protected static function booted()
    {
        static::creating(function ($investmentPool) {
            // Set default status
            if (empty($investmentPool->status)) {
                $investmentPool->status = 'open';
            }
        });

        static::updating(function ($investmentPool) {
            // Logic moved to updated() hook to prevent double-deduction issues
        });

        static::updated(function ($investmentPool) {
            // Only process if partners field was actually changed
            if (!$investmentPool->wasChanged('partners')) {
                return;
            }
            
            $newPartners = $investmentPool->partners;
            
            if (!isset($newPartners) || !is_array($newPartners)) {
                return;
            }
            
            // Process each partner for rebalancing
            foreach ($newPartners as $partner) {
                if (empty($partner['investor_id']) || !isset($partner['investment_amount'])) {
                    continue;
                }
                
                $investorId = intval($partner['investor_id']);
                $newAmount = floatval($partner['investment_amount']);
                
                // Find existing wallet allocation (source of truth)
                $allocation = \App\Models\WalletAllocation::where([
                    'investor_id' => $investorId,
                    'investment_pool_id' => $investmentPool->id
                ])->first();
                
                if (!$allocation) {
                    // Skip if no allocation exists - don't create new ones on update
                    continue;
                }
                
                // Calculate difference
                $difference = $newAmount - $allocation->amount;
                
                if ($difference == 0) {
                    // No change needed
                    continue;
                }
                
                $wallet = \App\Models\Wallet::where('investor_id', $investorId)->first();
                if (!$wallet) {
                    continue;
                }
                
                // Update allocation amount first
                $oldAmount = $allocation->amount;
                $allocation->amount = $newAmount;
                $allocation->save();
                
                // Handle the difference
                if ($difference > 0) {
                    // Amount increased: move money from Available → ActiveInvested
                    // Record as normal investment
                    \App\Models\WalletLedger::createInvestment(
                        $wallet, 
                        $difference, 
                        $allocation, 
                        "Additional investment for pool #{$investmentPool->id}"
                    );
                    
                    Log::info('Pool edit - amount increased', [
                        'pool_id' => $investmentPool->id,
                        'investor_id' => $investorId,
                        'old_amount' => $oldAmount,
                        'new_amount' => $newAmount,
                        'difference' => $difference,
                        'type' => 'investment'
                    ]);
                } else {
                    // Amount decreased: move money from ActiveInvested → Available
                    // Use pool adjustment type (does NOT affect total_returned)
                    \App\Models\WalletLedger::createPoolAdjustment(
                        $wallet, 
                        abs($difference), 
                        "Pool adjustment - investment reduced for pool #{$investmentPool->id}"
                    );
                    
                    Log::info('Pool edit - amount decreased', [
                        'pool_id' => $investmentPool->id,
                        'investor_id' => $investorId,
                        'old_amount' => $oldAmount,
                        'new_amount' => $newAmount,
                        'difference' => $difference,
                        'type' => 'pool_adjustment'
                    ]);
                }
            }
            
            // Update pool totals based on wallet allocations (source of truth)
            $totalCollected = \App\Models\WalletAllocation::where('investment_pool_id', $investmentPool->id)
                ->sum('amount');
            
            $investmentPool->total_collected = $totalCollected;
            
            // Calculate percentage_collected
            if ($investmentPool->amount_required > 0) {
                $investmentPool->percentage_collected = min(100, round(($totalCollected / $investmentPool->amount_required) * 100, 0));
            } else {
                $investmentPool->percentage_collected = 0;
            }
            
            // Calculate remaining_amount
            $investmentPool->remaining_amount = max(0, $investmentPool->amount_required - $totalCollected);
            
            // Update status based on remaining amount
            if ($investmentPool->remaining_amount > 0) {
                $investmentPool->status = self::STATUS_OPEN;
            } else {
                $investmentPool->status = self::STATUS_ACTIVE;
            }
            
            // Save without firing events again
            $investmentPool->saveQuietly();
        });

        static::created(function ($investmentPool) {
            Log::info('InvestmentPool created event fired', [
                'pool_id' => $investmentPool->id,
                'partners' => $investmentPool->partners
            ]);
            
            // Send notification to Agency Owner's investors when a new pool is created
            $agencyOwner = \App\Models\User::find($investmentPool->user_id);
            
            if ($agencyOwner) {
                $investors = \App\Models\User::where('invited_by', $agencyOwner->id)->get();
                
                foreach ($investors as $investor) {
                    Notification::make()
                        ->title('New Investment Pool Created')
                        ->body("New pool '{$investmentPool->design_name}' has been created by your Agency Owner.")
                        ->success()
                        ->sendToDatabase($investor);
                }
            }
            
            // Calculate totals with smart distribution logic
            if (isset($investmentPool->partners) && is_array($investmentPool->partners)) {
                $partners = $investmentPool->partners;
                $amountRequired = floatval($investmentPool->amount_required);
                $numberOfPartners = intval($investmentPool->number_of_partners);
                
                // Check if partners have custom investment amounts
                $hasCustomAmounts = false;
                $totalCollected = 0;
                $validPartners = [];
                
                foreach ($partners as $index => $partner) {
                    if (!empty($partner['investor_id'])) {
                        $validPartners[] = $index;
                        
                        if (isset($partner['investment_amount']) && floatval($partner['investment_amount']) > 0) {
                            $hasCustomAmounts = true;
                            $totalCollected += floatval($partner['investment_amount']);
                        }
                    }
                }
                
                // If no custom amounts, apply equal distribution as default
                if (!$hasCustomAmounts && $numberOfPartners > 0 && $amountRequired > 0) {
                    $perPartnerAmount = $amountRequired / $numberOfPartners;
                    $perPartnerPercentage = (100 / $numberOfPartners);
                    
                    foreach ($validPartners as $index) {
                        $partners[$index]['investment_amount'] = round($perPartnerAmount, 0);
                        $partners[$index]['investment_percentage'] = round($perPartnerPercentage, 2);
                        $totalCollected += floatval($partners[$index]['investment_amount']);
                    }
                } 
                // If custom amounts exist, calculate percentages based on actual amounts
                elseif ($hasCustomAmounts && $totalCollected > 0) {
                    foreach ($validPartners as $index) {
                        $investmentAmount = floatval($partners[$index]['investment_amount']);
                        $calculatedPercentage = round(($investmentAmount / $totalCollected) * 100, 2);
                        $partners[$index]['investment_percentage'] = $calculatedPercentage;
                    }
                }
                
                // Update partners with calculated data
                $investmentPool->partners = $partners;
                
                // Update pool totals
                $investmentPool->total_collected = $totalCollected;
                $investmentPool->percentage_collected = (int) min(100, round(($totalCollected / $amountRequired) * 100));
                $investmentPool->remaining_amount = max(0, $amountRequired - $totalCollected);
                
                // Update status based on remaining amount
                if ($investmentPool->remaining_amount > 0) {
                    $investmentPool->status = self::STATUS_OPEN;
                } else {
                    $investmentPool->status = self::STATUS_ACTIVE;
                }
                
                $investmentPool->saveQuietly();
            }
            
            // Process wallet allocations for partners
            if (isset($investmentPool->partners) && is_array($investmentPool->partners)) {
                Log::info('All partners data', [
                    'pool_id' => $investmentPool->id,
                    'partners' => $investmentPool->partners
                ]);
                
                $partnersData = array_filter($investmentPool->partners, function($partner) {
                    $hasInvestorId = !empty($partner['investor_id']);
                    $hasAmount = !empty($partner['investment_amount']);
                    
                    Log::info('Checking partner', [
                        'investor_id' => $partner['investor_id'] ?? 'null',
                        'investment_amount' => $partner['investment_amount'] ?? 'null',
                        'has_investor_id' => $hasInvestorId,
                        'has_amount' => $hasAmount
                    ]);
                    
                    return $hasInvestorId && $hasAmount;
                });
                
                Log::info('Processing wallet allocations', [
                    'pool_id' => $investmentPool->id,
                    'total_partners' => count($investmentPool->partners),
                    'valid_partners' => count($partnersData)
                ]);
                
                foreach ($partnersData as $partner) {
                    $investorId = intval($partner['investor_id']);
                    $investmentAmount = floatval($partner['investment_amount']);
                    
                    Log::info('Processing partner', [
                        'investor_id' => $investorId,
                        'amount' => $investmentAmount
                    ]);
                    
                    if (!$investorId || $investmentAmount <= 0) {
                        Log::error('Invalid partner data', [
                            'investor_id' => $investorId,
                            'amount' => $investmentAmount
                        ]);
                        continue;
                    }
                    
                    // Find investor's wallet
                    $wallet = \App\Models\Wallet::where('investor_id', $investorId)->first();
                    
                    if ($wallet) {
                        Log::info('Wallet found', [
                            'wallet_id' => $wallet->id,
                            'balance' => $wallet->amount
                        ]);
                        
                        // Check if wallet has sufficient balance
                        if ($wallet->available_balance < $investmentAmount) {
                            Log::error('Insufficient funds', [
                                'investor_id' => $investorId,
                                'available' => $wallet->available_balance,
                                'required' => $investmentAmount
                            ]);
                            continue;
                        }
                        
                        try {
                            // Create wallet allocation first
                            $allocation = \App\Models\WalletAllocation::create([
                                'wallet_id' => $wallet->id,
                                'investor_id' => $investorId,
                                'investment_pool_id' => $investmentPool->id,
                                'amount' => $investmentAmount,
                            ]);
                            
                            // Then create ledger entry using the allocation
                            \App\Models\WalletLedger::createInvestment($wallet, $allocation->amount, $allocation, "Investment in pool #{$investmentPool->id}");
                            
                            if ($allocation) {
                                Log::info('Wallet allocation created successfully', [
                                    'allocation_id' => $allocation->id,
                                    'investor_id' => $investorId,
                                    'amount' => $investmentAmount,
                                    'pool_id' => $investmentPool->id
                                ]);
                            } else {
                                Log::error('Failed to create allocation', ['investor_id' => $investorId]);
                            }
                        } catch (\Exception $e) {
                            Log::error('Exception during wallet allocation', [
                                'investor_id' => $investorId,
                                'error' => $e->getMessage(),
                                'trace' => $e->getTraceAsString()
                            ]);
                        }
                    } else {
                        Log::error('Wallet not found for investor', ['investor_id' => $investorId]);
                    }
                }
            }
        });
    }
    
    public function walletAllocations()
    {
        return $this->hasMany(\App\Models\WalletAllocation::class, 'investment_pool_id');
    }
    
    

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function lat()
    {
        return $this->belongsTo(\App\Models\Lat::class);
    }
    
    public function getRemainingAmountAttribute()
    {
        return max(0, $this->amount_required - $this->total_collected);
    }
    
    public function getIsFullyFundedAttribute()
    {
        return $this->collected_amount >= $this->required_amount;
    }
    
    public function getPercentageFundedAttribute()
    {
        if ($this->amount_required <= 0) {
            return 0;
        }
        return min(100, round(($this->total_collected / $this->amount_required) * 100, 2));
    }

        public function investors()
    {
        return $this->belongsToMany(User::class, 'investment_pool_user')
            ->withPivot('investment_amount', 'investment_percentage')
            ->withTimestamps();
    }
}
