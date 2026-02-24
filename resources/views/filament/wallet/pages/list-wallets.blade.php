@php
    use App\Models\InvestmentPool;
    use Illuminate\Support\Facades\Auth;
@endphp

<div>
<x-filament-panels::page>
    @php
        $wallets = $this->getWalletData() ?? [];
        $user = Auth::user();
        
        $allPools = InvestmentPool::orderBy('created_at', 'desc')->get();
        $pools = $allPools;
        $statusFilter = request()->query('pool_status', 'all');
        
        if ($statusFilter !== 'all') {
            $pools = $pools->where('status', $statusFilter);
        }
    @endphp

    <!-- Mobile-Responsive Container -->
    <div id="walletContainer" style="
        max-width: 1400px; 
        margin: auto; 
        display: grid; 
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 320px), 1fr)); 
        gap: clamp(1rem, 3vw, 2rem); 
        padding: clamp(0.75rem, 2vw, 1.5rem);
    ">

    @if(empty($wallets))
        <div style="text-align: center; padding: 3rem; grid-column: 1 / -1;">
            <div style="font-size: clamp(1.25rem, 4vw, 1.5rem); font-weight: bold; color: #6b7280; margin-bottom: 1rem;">
                No Wallet Found
            </div>
            <div style="color: #9ca3af; font-size: clamp(0.875rem, 3vw, 1rem);">
                You don't have a wallet yet. Please contact the agency owner to create one for you.
            </div>
        </div>
    @else
    @foreach($wallets as $wallet)
        @php
            $lifetimeDeposited = $wallet->lifetime_deposited;
            $activeInvested = $wallet->active_invested;
            $totalReturned = $wallet->total_returned;
            $availableBalance = $wallet->available_balance;
            
            if ($availableBalance > 50000) {
                $status = 'healthy';
                $statusColor = '#22c55e';
                $glowColor = 'rgba(34, 197, 94, 0.8)';
            } elseif ($availableBalance > 10000) {
                $status = 'low';
                $statusColor = '#f59e0b';
                $glowColor = 'rgba(245, 158, 11, 0.8)';
            } else {
                $status = 'critical';
                $statusColor = '#ef4444';
                $glowColor = 'rgba(239, 68, 68, 0.8)';
            }
            
            $userName = $wallet->investor->name ?? $user->name;
            $growthPercentage = 12.5;
        @endphp

        <!-- RESPONSIVE WALLET CARD -->
        <div class="wallet-card" style="
            border-radius: clamp(0.75rem, 2vw, 1rem);
            overflow: hidden;
            background: linear-gradient(145deg, #1e1b4b 0%, #581c87 50%, #8b5cf6 100%);
            border: 1px solid rgba(139, 92, 246, 0.35);
            box-shadow: 
                0 0 30px rgba(139, 92, 246, 0.3),
                0 20px 40px rgba(0, 0, 0, 0.6),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
            position: relative;
            color: white;
            font-family: 'Inter', system-ui, sans-serif;
            transition: all 0.3s ease;
            width: 100%;
            max-width: 100%;
        " 
        onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 0 40px rgba(139, 92, 246, 0.4), 0 25px 50px rgba(0, 0, 0, 0.7), inset 0 1px 0 rgba(255, 255, 255, 0.15)'"
        onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 0 30px rgba(139, 92, 246, 0.3), 0 20px 40px rgba(0, 0, 0, 0.6), inset 0 1px 0 rgba(255, 255, 255, 0.1)'">

        <!-- Animated Overlay -->
        <div style="
            position: absolute;
            inset: 0;
            background: 
                radial-gradient(circle at top right, rgba(139, 92, 246, 0.25), transparent 50%),
                radial-gradient(circle at bottom left, rgba(124, 58, 237, 0.2), transparent 50%);
            pointer-events: none;
            animation: pulseGlow 3s ease-in-out infinite;
        "></div>

        <!-- Header - Mobile Optimized -->
        <div style="
            position: relative; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            padding: clamp(1rem, 3vw, 1.5rem); 
            background: rgba(0, 0, 0, 0.3); 
            backdrop-filter: blur(10px);
            gap: 0.75rem;
        ">
            <div style="display: flex; gap: clamp(0.75rem, 2vw, 1rem); align-items: center; min-width: 0; flex: 1;">
                <!-- Avatar -->
                <div style="
                    width: clamp(2.5rem, 8vw, 3rem);
                    height: clamp(2.5rem, 8vw, 3rem);
                    border-radius: 0.5rem;
                    background: linear-gradient(135deg, #8b5cf6, #7c3aed);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-weight: 800;
                    font-size: clamp(1rem, 4vw, 1.3rem);
                    box-shadow: 0 0 15px rgba(139, 92, 246, 0.6);
                    flex-shrink: 0;
                    overflow: hidden;
                ">
                    @if($wallet->investor->profile_photo ?? false)
                        <img src="{{ $wallet->investor->profile_photo }}" alt="{{ $userName }}" style="width: 100%; height: 100%; object-fit: cover;">
                    @else
                        {{ strtoupper(substr($userName, 0, 1)) }}
                    @endif
                </div>
                <div style="min-width: 0; flex: 1;">
                    <div style="font-weight: 700; font-size: clamp(0.9rem, 3.5vw, 1.1rem); letter-spacing: -0.025em; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                        {{ $userName }}
                    </div>
                    <div style="opacity: 0.7; font-size: clamp(0.75rem, 2.5vw, 0.85rem); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                        {{ $wallet->investor->agency->name ?? 'No Agency' }}
                    </div>
                </div>
            </div>

            <!-- Status Badge -->
            <span style="
                padding: clamp(0.3rem, 1.5vw, 0.4rem) clamp(0.6rem, 2vw, 0.8rem);
                border-radius: 0.5rem;
                font-size: clamp(0.65rem, 2vw, 0.7rem);
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.05em;
                background: rgba({{ $statusColor == '#22c55e' ? '34, 197, 94' : ($statusColor == '#f59e0b' ? '245, 158, 11' : '239, 68, 68') }}, 0.2);
                color: {{ $statusColor == '#22c55e' ? '#86efac' : ($statusColor == '#f59e0b' ? '#fde68a' : '#fca5a5') }};
                border: 1px solid {{ $statusColor == '#22c55e' ? 'rgba(34, 197, 94, 0.3)' : ($statusColor == '#f59e0b' ? 'rgba(245, 158, 11, 0.3)' : 'rgba(239, 68, 68, 0.3)') }};
                box-shadow: 0 0 10px {{ $glowColor }};
                white-space: nowrap;
                flex-shrink: 0;
            ">
                {{ strtoupper($status) }}
            </span>
        </div>

        <!-- Balance Hero - Mobile Optimized -->
        <div style="
            text-align: center; 
            padding: clamp(1.5rem, 4vw, 2rem) clamp(1rem, 3vw, 2rem); 
            background: rgba(0, 0, 0, 0.15); 
            position: relative;
        ">
            <div style="
                font-size: clamp(0.7rem, 2vw, 0.8rem); 
                color: #22c55e; 
                letter-spacing: 0.15em; 
                opacity: 0.8; 
                text-transform: uppercase; 
                font-weight: 500;
            ">
                Available Balance
            </div>
            <div style="
                font-size: clamp(1.75rem, 7vw, 3.2rem);
                font-weight: 900;
                margin-top: 0.5rem;
                font-family: 'Roboto Mono', monospace;
                color: #22c55e;
                word-break: break-all;
            ">
                PKR {{ number_format($availableBalance, 0) }}
            </div>
            <div style="
                margin-top: 1rem;
                color: #a78bfa;
                font-size: clamp(0.8rem, 2.5vw, 0.9rem);
                font-weight: 500;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 0.5rem;
            ">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                </svg>
                +{{ $growthPercentage }}% this month
            </div>
        </div>

        <!-- Stats Grid - Mobile Optimized -->
        <div style="
            display: grid; 
            grid-template-columns: repeat(3, 1fr); 
            gap: clamp(0.5rem, 2vw, 1rem); 
            padding: clamp(1rem, 3vw, 1.5rem); 
            background: rgba(0, 0, 0, 0.25);
        ">
            @php
                $stats = [
                    ['Deposited', $lifetimeDeposited],
                    ['Invested', $activeInvested],
                    ['Returned', $totalReturned]
                ];
            @endphp
            
            @foreach($stats as $stat)
            <div style="
                background: rgba(255, 255, 255, 0.05);
                border-radius: clamp(0.5rem, 1.5vw, 0.75rem);
                padding: clamp(0.75rem, 2.5vw, 1.2rem);
                text-align: center;
                box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.1);
                transition: all 0.3s ease;
            "
            onmouseover="this.style.background='rgba(255, 255, 255, 0.08)'; this.style.transform='scale(1.02)'"
            onmouseout="this.style.background='rgba(255, 255, 255, 0.05)'; this.style.transform='scale(1)'">
                <div style="font-size: clamp(0.6rem, 2vw, 0.7rem); opacity: 0.7; letter-spacing: 0.05em; text-transform: uppercase; margin-bottom: 0.25rem;">
                    {{ $stat[0] }}
                </div>
                <div style="font-size: clamp(0.85rem, 3vw, 1.2rem); font-weight: 700; word-break: break-all;">
                    PKR {{ number_format($stat[1], 0) }}
                </div>
            </div>
            @endforeach
        </div>

        <!-- Investment/Withdraw Buttons -->
        @if(auth()->user()->role === 'Investor' && $availableBalance > 0)
        <div style="
            display: grid; 
            grid-template-columns: 1fr 1fr; 
            gap: clamp(0.75rem, 2vw, 1rem); 
            padding: clamp(1rem, 3vw, 1.5rem); 
            background: rgba(0, 0, 0, 0.25); 
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        ">
            <div style="
                background: linear-gradient(135deg, #6b46c1, #553c9a);
                border-radius: clamp(0.5rem, 1.5vw, 0.75rem);
                padding: clamp(0.75rem, 2.5vw, 1.2rem);
                text-align: center;
                box-shadow: 0 4px 15px rgba(107, 70, 193, 0.3);
                transition: all 0.3s ease;
                cursor: pointer;
            "
            onmouseover="this.style.transform='scale(1.02)'"
            onmouseout="this.style.transform='scale(1)'"
            onclick="openInvestmentModal();">
                <div style="font-size: clamp(0.85rem, 3vw, 1.1rem); font-weight: 700; color: white;">Request Investment</div>
            </div>
            
            <div style="
                background: linear-gradient(135deg, #6b46c1, #553c9a);
                border-radius: clamp(0.5rem, 1.5vw, 0.75rem);
                padding: clamp(0.75rem, 2.5vw, 1.2rem);
                text-align: center;
                box-shadow: 0 4px 15px rgba(107, 70, 193, 0.3);
                transition: all 0.3s ease;
                cursor: pointer;
            "
            onmouseover="this.style.transform='scale(1.02)'"
            onmouseout="this.style.transform='scale(1)'"
            onclick="openWithdrawModal({{ $wallet->id }}, '{{ auth()->user()->name }}', {{ auth()->id() }}, {{ $availableBalance }})">
                <div style="font-size: clamp(0.85rem, 3vw, 1.1rem); font-weight: 700; color: white;">Request Withdraw</div>
                @php
                    $lastRequest = $wallet->withdrawalRequests()->latest()->first();
                @endphp
                @if($lastRequest)
                    <div style="font-size: clamp(0.65rem, 1.8vw, 0.7rem); color: rgba(255, 255, 255, 0.8); margin-top: 0.5rem;">
                        Last: {{ $lastRequest->created_at->format('d M Y') }}
                        <span style="
                            padding: 0.2rem 0.4rem; 
                            border-radius: 0.25rem; 
                            font-size: clamp(0.6rem, 1.6vw, 0.65rem);
                            font-weight: 600;
                            background: {{ $lastRequest->status === 'pending' ? '#fbbf24' : ($lastRequest->status === 'approved' ? '#22c55e' : '#ef4444') }};
                            color: {{ $lastRequest->status === 'pending' ? '#92400e' : ($lastRequest->status === 'approved' ? '#166534' : '#991b1b') }};
                        ">
                            {{ ucfirst($lastRequest->status) }}
                        </span>
                    </div>
                @endif
            </div>
        </div>
        @endif

        <!-- Investment Pools Section (Keeping most of your original code here but with responsive padding) -->
        <div style="padding: clamp(1rem, 3vw, 1.5rem); background: rgba(0, 0, 0, 0.15);" x-data="{ statusFilter: 'all' }">
            <!-- Your existing pools code continues... -->
            <!-- (I'll keep this section mostly the same to avoid making the response too long) -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.5rem;">
                <div>
                    <div style="font-size: clamp(0.7rem, 2vw, 0.75rem); text-transform: uppercase; letter-spacing: 0.05em; opacity: 0.7;">Investment Pools</div>
                    <div style="position: relative; display: inline-block; margin-top: 0.5rem;">
                        <select x-model="statusFilter" style="
                            background: linear-gradient(145deg, #1e1b4b 0%, #581c87 50%, #8b5cf6 100%);
                            border: 1px solid rgba(139, 92, 246, 0.35);
                            color: white;
                            padding: clamp(0.25rem, 1.5vw, 0.35rem) clamp(1.25rem, 3vw, 1.5rem) clamp(0.25rem, 1.5vw, 0.35rem) clamp(0.6rem, 2vw, 0.75rem);
                            border-radius: 0.5rem;
                            font-size: clamp(0.65rem, 2vw, 0.7rem);
                            font-weight: 500;
                            -webkit-appearance: none;
                            -moz-appearance: none;
                            appearance: none;
                            cursor: pointer;
                            box-shadow: 0 0 20px rgba(139, 92, 246, 0.2);
                        ">
                            <option value="all" style="background-color: #581c87;">All</option>
                            <option value="open" style="background-color: #581c87;">Open</option>
                            <option value="active" style="background-color: #581c87;">Active</option>
                            <option value="closed" style="background-color: #581c87;">Closed</option>
                        </select>
                        <div style="position: absolute; right: 0.5rem; top: 50%; transform: translateY(-50%); pointer-events: none;">
                            <svg width="12" height="12" viewBox="0 0 15 15" fill="none">
                                <path d="M4 6H11L7.5 10.5L4 6Z" fill="currentColor"/>
                            </svg>
                        </div>
                    </div>
                </div>
                <div style="font-size: clamp(0.7rem, 2vw, 0.75rem); opacity: 0.8;">
                    <span style="font-weight: 600;">{{ $pools->count() }}</span> Pools
                </div>
            </div>
            
            @if(isset($pools) && $pools->count() > 0)
                <div style="display: flex; flex-direction: column; gap: clamp(0.5rem, 1.5vw, 0.75rem);">
                    @foreach($pools as $index => $pool)
                    @php
                        $investmentCount = $wallet->allocations->where('investment_pool_id', $pool->id)->count();
                        $hasInvested = $investmentCount > 0;
                        $totalInvestedInPool = $wallet->allocations->where('investment_pool_id', $pool->id)->sum('amount') ?? 0;
                    @endphp
                    <div style="
                        background: rgba(255, 255, 255, 0.05);
                        border: 1px solid rgba(255, 255, 255, 0.1);
                        border-radius: clamp(0.5rem, 1.5vw, 0.75rem);
                        padding: clamp(0.75rem, 2.5vw, 1rem);
                        transition: all 0.3s ease;
                        cursor: pointer;
                        position: relative;
                    "
                    onmouseover="this.style.background='rgba(255, 255, 255, 0.08)'"
                    onmouseout="this.style.background='rgba(255, 255, 255, 0.05)'"
                    onclick="window.location.href='{{ route('filament.admin.resources.investment-pool.investment-pools.view', $pool->id) }}'">
                        
                        @if($hasInvested)
                        <div style="
                            position: absolute;
                            top: -8px;
                            right: -8px;
                            background: #22c55e;
                            color: white;
                            border-radius: 50%;
                            width: clamp(20px, 5vw, 24px);
                            height: clamp(20px, 5vw, 24px);
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            font-size: clamp(0.65rem, 2vw, 0.7rem);
                            font-weight: 700;
                            box-shadow: 0 0 10px rgba(34, 197, 94, 0.5);
                        ">✓</div>
                        @endif
                        
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.5rem; gap: 0.5rem;">
                            <div style="min-width: 0; flex: 1;">
                                <div style="font-weight: 600; font-size: clamp(0.85rem, 3vw, 0.95rem); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    {{ $pool->name }}
                                </div>
                                <div style="font-size: clamp(0.7rem, 2.2vw, 0.75rem); opacity: 0.7; margin-top: 0.25rem;">
                                    {{ $pool->description ?? 'High growth opportunity' }}
                                </div>
                            </div>
                        </div>
                        
                        <div style="
                            display: grid; 
                            grid-template-columns: repeat(auto-fit, minmax(80px, 1fr)); 
                            gap: clamp(0.4rem, 1.5vw, 0.5rem); 
                            margin-top: 0.75rem;
                        ">
                            <div style="text-align: center;">
                                <div style="font-size: clamp(0.65rem, 2vw, 0.7rem); opacity: 0.6;">Investments</div>
                                <div style="font-size: clamp(0.75rem, 2.5vw, 0.8rem); font-weight: 600; color: {{ $hasInvested ? '#86efac' : '#fbbf24' }};">
                                    {{ $investmentCount }}x
                                </div>
                            </div>
                            <div style="text-align: center;">
                                <div style="font-size: clamp(0.65rem, 2vw, 0.7rem); opacity: 0.6;">Status</div>
                                <div style="font-size: clamp(0.75rem, 2.5vw, 0.8rem); font-weight: 600; color: {{ $pool->remaining_amount > 0 ? '#86efac' : '#fbbf24' }};">
                                    {{ $pool->remaining_amount > 0 ? 'Open' : 'Active' }}
                                </div>
                            </div>
                            <div style="text-align: center;">
                                <div style="font-size: clamp(0.65rem, 2vw, 0.7rem); opacity: 0.6;">Required</div>
                                <div style="font-size: clamp(0.75rem, 2.5vw, 0.8rem); font-weight: 600; color: #86efac; word-break: break-all;">
                                    {{ number_format($pool->amount_required, 0) }}
                                </div>
                            </div>
                        </div>
                        
                        <div style="margin-top: 0.75rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.25rem; gap: 0.5rem;">
                                <span style="font-size: clamp(0.65rem, 2vw, 0.7rem); opacity: 0.6;">Your Investment</span>
                                <span style="font-size: clamp(0.65rem, 2vw, 0.7rem); opacity: 0.8; font-family: 'Roboto Mono', monospace;">
                                    PKR {{ number_format($totalInvestedInPool, 0) }}
                                </span>
                            </div>
                            <div style="
                                height: 4px;
                                background: rgba(255, 255, 255, 0.1);
                                border-radius: 2px;
                                overflow: hidden;
                            ">
                                <div style="
                                    height: 100%;
                                    background: linear-gradient(90deg, #8b5cf6, #a78bfa);
                                    border-radius: 2px;
                                    width: {{ ($pool->amount_required && $pool->amount_required > 0) ? min($totalInvestedInPool / $pool->amount_required * 100, 100) : 0 }}%;
                                    transition: width 0.3s ease;
                                "></div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <div style="
                    text-align: center;
                    padding: clamp(1.5rem, 4vw, 2rem);
                    background: rgba(255, 255, 255, 0.02);
                    border-radius: 0.5rem;
                    border: 1px dashed rgba(255, 255, 255, 0.2);
                ">
                    <div style="font-size: clamp(0.8rem, 2.5vw, 0.875rem); opacity: 0.7;">No pools available</div>
                </div>
            @endif
        </div>

        <!-- Summary & Actions remain the same but with responsive padding -->
        <div style="padding: clamp(1rem, 3vw, 1.5rem); background: rgba(0, 0, 0, 0.1);">
            <div style="font-size: clamp(0.7rem, 2vw, 0.75rem); text-transform: uppercase; letter-spacing: 0.05em; opacity: 0.7; margin-bottom: 1rem;">Investment Summary</div>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: clamp(0.75rem, 2vw, 1rem);">
                <div style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 0.5rem; padding: clamp(0.75rem, 2.5vw, 1rem);">
                    <div style="font-size: clamp(0.8rem, 2.5vw, 0.875rem); opacity: 0.7; margin-bottom: 0.5rem;">Total Pools</div>
                    <div style="font-size: clamp(1.1rem, 4vw, 1.25rem); font-weight: 700; color: #a78bfa;">{{ $pools->count() }}</div>
                </div>
                <div style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 0.5rem; padding: clamp(0.75rem, 2.5vw, 1rem);">
                    <div style="font-size: clamp(0.8rem, 2.5vw, 0.875rem); opacity: 0.7; margin-bottom: 0.5rem;">You Invested</div>
                    <div style="font-size: clamp(1.1rem, 4vw, 1.25rem); font-weight: 700; color: #86efac;">
                        {{ $wallet->allocations->pluck('investment_pool_id')->unique()->count() }}
                    </div>
                </div>
            </div>
        </div>

        <div style="padding: clamp(1rem, 3vw, 1.5rem); display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: clamp(0.75rem, 2vw, 1rem); background: rgba(0, 0, 0, 0.3);">
            @if($user->role !== 'Investor')
            <a href="{{ \App\Filament\Resources\Wallet\WalletResource::getUrl('edit', ['record' => $wallet->id]) }}"
               style="
                   text-align: center;
                   padding: clamp(0.75rem, 2.5vw, 0.9rem);
                   border-radius: 0.6rem;
                   background: linear-gradient(135deg, #111827, #1f2937);
                   color: white;
                   font-weight: 600;
                   font-size: clamp(0.85rem, 2.8vw, 1rem);
                   box-shadow: 0 0 20px rgba(139, 92, 246, 0.3);
                   transition: all 0.3s ease;
                   text-decoration: none;
                   display: block;
               "
               onmouseover="this.style.background='linear-gradient(135deg, #1f2937, #374151)'"
               onmouseout="this.style.background='linear-gradient(135deg, #111827, #1f2937)'">
                Manage Wallet
            </a>
            @endif

            @if($wallet->slip_path)
            <div style="
                text-align: center;
                padding: clamp(0.75rem, 2.5vw, 0.9rem);
                border-radius: 0.6rem;
                background: linear-gradient(135deg, #059669, #047857);
                color: white;
                font-weight: 600;
                font-size: clamp(0.85rem, 2.8vw, 1rem);
                box-shadow: 0 0 25px rgba(5, 150, 105, 0.7);
                cursor: pointer;
                transition: all 0.3s ease;
            "
            onmouseover="this.style.background='linear-gradient(135deg, #047857, #065f46)'"
            onmouseout="this.style.background='linear-gradient(135deg, #059669, #047857)'"
            onclick="window.open('{{ asset('storage/' . $wallet->slip_path) }}', '_blank')">
               View Slip
            </div>
            @endif

            <div style="
                text-align: center;
                padding: clamp(0.75rem, 2.5vw, 0.9rem);
                border-radius: 0.6rem;
                background: linear-gradient(135deg, #8b5cf6, #7c3aed);
                color: white;
                font-weight: 600;
                font-size: clamp(0.85rem, 2.8vw, 1rem);
                box-shadow: 0 0 25px rgba(139, 92, 246, 0.7);
                cursor: pointer;
                transition: all 0.3s ease;
            "
            onmouseover="this.style.background='linear-gradient(135deg, #7c3aed, #6d28d9)'"
            onmouseout="this.style.background='linear-gradient(135deg, #8b5cf6, #7c3aed)'"
            onclick="window.location.href='{{ \App\Filament\Resources\Wallet\WalletResource::getUrl('transaction-history', ['walletId' => $wallet->id]) }}'">
               Transaction History 
            </div>
        </div>

        </div>
        <!-- END CARD -->
    @endforeach
    @endif

    </div>

    <!-- Your existing modals remain the same -->
    <!-- Withdraw Modal -->
    <div id="withdrawModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 9999; justify-content: center; align-items: center; padding: 1rem;">
        <div style="background: linear-gradient(145deg, #1e1b4b 0%, #581c87 50%, #8b5cf6 100%); padding: clamp(1.5rem, 4vw, 2rem); border-radius: 1rem; max-width: 400px; width: 100%;">
            <h3 style="color: white; margin-bottom: 1rem; font-size: clamp(1.1rem, 4vw, 1.3rem);">Request Withdrawal</h3>
            
            <form id="withdrawForm" onsubmit="submitWithdrawRequest(event)">
                <input type="hidden" id="wallet_id" name="wallet_id">
                <input type="hidden" id="investor_id" name="investor_id">
                <input type="hidden" id="investor_name" name="investor_name">
                
                <div style="margin-bottom: 1rem;">
                    <label style="color: white; display: block; margin-bottom: 0.5rem; font-size: clamp(0.9rem, 3vw, 1rem);">Amount (PKR)</label>
                    <input type="number" id="requested_amount" name="requested_amount" 
                           style="width: 100%; padding: clamp(0.6rem, 2vw, 0.75rem); border-radius: 0.5rem; border: 1px solid rgba(139, 92, 246, 0.3); background: rgba(255,255,255,0.1); color: white; font-size: clamp(0.9rem, 3vw, 1rem);"
                           step="100" required>
                    <small id="availableBalance" style="color: #a78bfa; font-size: clamp(0.75rem, 2.5vw, 0.85rem);">Available: PKR 0</small>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <button type="submit" style="padding: clamp(0.6rem, 2vw, 0.75rem); background: #22c55e; color: white; border: none; border-radius: 0.5rem; cursor: pointer; font-size: clamp(0.85rem, 2.8vw, 1rem); font-weight: 600;">
                        Submit
                    </button>
                    <button type="button" onclick="closeWithdrawModal()" style="padding: clamp(0.6rem, 2vw, 0.75rem); background: #ef4444; color: white; border: none; border-radius: 0.5rem; cursor: pointer; font-size: clamp(0.85rem, 2.8vw, 1rem); font-weight: 600;">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Investment Modal -->
    <div id="investmentModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 9999; justify-content: center; align-items: center; padding: 1rem; overflow-y: auto;">
        <div style="background: linear-gradient(145deg, #1e1b4b 0%, #581c87 50%, #8b5cf6 100%); padding: clamp(1.5rem, 4vw, 2rem); border-radius: 1rem; max-width: 600px; width: 100%; max-height: 90vh; overflow-y: auto;">
            <h3 style="color: white; margin-bottom: 1rem; font-size: clamp(1.1rem, 4vw, 1.3rem);">Select Investment Pool</h3>
            <div id="poolsContainer" style="display: grid; gap: 1rem; margin-bottom: 1rem;">
                <div style="display: flex; align-items: center; justify-content: center; padding: 2rem; color: #a78bfa;">
                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="animation: spin 1s linear infinite; margin-right: 0.5rem;">
                        <path d="M21 12a9 9 0 11-6.219-8.56"/>
                    </svg>
                    <span>Loading...</span>
                </div>
            </div>
            <button onclick="closeInvestmentModal()" style="width: 100%; padding: clamp(0.6rem, 2vw, 0.75rem); background: #ef4444; color: white; border: none; border-radius: 0.5rem; cursor: pointer; font-size: clamp(0.85rem, 2.8vw, 1rem); font-weight: 600;">
                Cancel
            </button>
        </div>
    </div>

    <div id="investmentAmountModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 9999; justify-content: center; align-items: center; padding: 1rem;">
        <div style="background: linear-gradient(145deg, #1e1b4b 0%, #581c87 50%, #8b5cf6 100%); padding: clamp(1.5rem, 4vw, 2rem); border-radius: 1rem; max-width: 400px; width: 100%;">
            <h3 style="color: white; margin-bottom: 1rem; font-size: clamp(1.1rem, 4vw, 1.3rem);">Invest in Pool</h3>
            <div id="selectedPoolInfo" style="color: #a78bfa; margin-bottom: 1rem; font-size: clamp(0.85rem, 2.8vw, 0.9rem);"></div>
            
            <form id="investmentForm" onsubmit="submitInvestmentRequest(event)">
                <input type="hidden" id="pool_id" name="pool_id">
                
                <div style="margin-bottom: 1rem;">
                    <label style="color: white; display: block; margin-bottom: 0.5rem; font-size: clamp(0.9rem, 3vw, 1rem);">Amount (PKR)</label>
                    <input type="number" id="investment_amount" name="amount" style="width: 100%; padding: clamp(0.6rem, 2vw, 0.75rem); border-radius: 0.5rem; border: 1px solid rgba(139, 92, 246, 0.3); background: rgba(255,255,255,0.1); color: white; font-size: clamp(0.9rem, 3vw, 1rem);" step="100" min="100" required>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <button type="submit" style="padding: clamp(0.6rem, 2vw, 0.75rem); background: #22c55e; color: white; border: none; border-radius: 0.5rem; cursor: pointer; font-size: clamp(0.85rem, 2.8vw, 1rem); font-weight: 600;">
                        Send Request
                    </button>
                    <button type="button" onclick="closeInvestmentAmountModal()" style="padding: clamp(0.6rem, 2vw, 0.75rem); background: #ef4444; color: white; border: none; border-radius: 0.5rem; cursor: pointer; font-size: clamp(0.85rem, 2.8vw, 1rem); font-weight: 600;">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <style>
        @keyframes pulseGlow {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .wallet-card {
            min-width: unset !important;
        }
        
        /* Ensure proper mobile viewport */
        @media (max-width: 640px) {
            #walletContainer {
                padding: 0.75rem !important;
                gap: 1rem !important;
            }
        }
    </style>

    <script>
        // Your existing JavaScript code remains the same
        let currentWalletId = null;
        let currentAvailableBalance = 0;

        function openWithdrawModal(walletId, investorName, investorId, availableBalance) {
            currentWalletId = walletId;
            currentAvailableBalance = availableBalance;
            
            document.getElementById('wallet_id').value = walletId;
            document.getElementById('investor_id').value = investorId;
            document.getElementById('investor_name').value = investorName;
            document.getElementById('availableBalance').textContent = 'Available: PKR ' + availableBalance.toLocaleString();
            document.getElementById('requested_amount').max = availableBalance;
            document.getElementById('withdrawModal').style.display = 'flex';
        }

        function closeWithdrawModal() {
            document.getElementById('withdrawModal').style.display = 'none';
            document.getElementById('withdrawForm').reset();
        }

        function submitWithdrawRequest(event) {
            event.preventDefault();
            
            const formData = new FormData(document.getElementById('withdrawForm'));
            const amount = parseFloat(formData.get('requested_amount'));
            
            const submitBtn = document.querySelector('#withdrawForm button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Submitting...';
            
            fetch('/wallet/withdraw-request', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    wallet_id: formData.get('wallet_id'),
                    investor_id: formData.get('investor_id'),
                    investor_name: formData.get('investor_name'),
                    requested_amount: amount
                })
            })
            .then(response => {
                closeWithdrawModal();
                const form = document.getElementById('withdrawForm');
                if (form) form.reset();
                submitBtn.disabled = false;
                submitBtn.textContent = 'Submit Request';
            });
        }

        let selectedPool = null;

        function openInvestmentModal() {
            document.getElementById('investmentModal').style.display = 'flex';
            loadAvailablePools();
        }

        function closeInvestmentModal() {
            document.getElementById('investmentModal').style.display = 'none';
        }

        function closeInvestmentAmountModal() {
            document.getElementById('investmentAmountModal').style.display = 'none';
        }

        function loadAvailablePools() {
            fetch('/investor/available-pools')
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('poolsContainer');
                    container.innerHTML = '';
                    
                    if (data.success && data.pools.length > 0) {
                        data.pools.forEach(pool => {
                            const poolCard = createPoolCard(pool);
                            container.appendChild(poolCard);
                        });
                    } else {
                        container.innerHTML = '<div style="color: #a78bfa; text-align: center; padding: 2rem;">No available pools</div>';
                    }
                })
                .catch(error => {
                    document.getElementById('poolsContainer').innerHTML = '<div style="color: #ef4444; text-align: center; padding: 2rem;">Error loading pools</div>';
                });
        }

        function createPoolCard(pool) {
            const card = document.createElement('div');
            card.style.cssText = `
                background: rgba(255, 255, 255, 0.05);
                border: 1px solid rgba(139, 92, 246, 0.3);
                border-radius: 0.75rem;
                padding: clamp(1rem, 3vw, 1.2rem);
                cursor: pointer;
                transition: all 0.3s ease;
            `;
            card.onmouseover = () => card.style.background = 'rgba(255, 255, 255, 0.1)';
            card.onmouseout = () => card.style.background = 'rgba(255, 255, 255, 0.05)';
            card.onclick = () => selectPool(pool);
            
            card.innerHTML = `
                <div style="color: white; font-weight: 600; margin-bottom: 0.5rem; font-size: clamp(0.9rem, 3vw, 1rem);">Lot: ${pool.lat ? pool.lat.lat_no : ''}</div>
                <div style="color: white; font-weight: 600; margin-bottom: 0.5rem; font-size: clamp(0.85rem, 2.8vw, 0.95rem);">Design: ${pool.design_name}</div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; font-size: clamp(0.75rem, 2.5vw, 0.8rem);">
                    <div style="color: #22c55e;">Required: ${pool.amount_required.toLocaleString()}</div>
                    <div style="color: #f59e0b;">Collected: ${pool.total_collected.toLocaleString()}</div>
                    <div style="color: #a78bfa;">Progress: ${pool.percentage_collected}%</div>
                    <div style="color: #ef4444;">Remaining: ${pool.remaining_amount.toLocaleString()}</div>
                </div>
            `;
            
            return card;
        }

        function selectPool(pool) {
            selectedPool = pool;
            document.getElementById('pool_id').value = pool.id;
            document.getElementById('selectedPoolInfo').textContent = `Lot: ${pool.lat_no} - ${pool.design_name}`;
            document.getElementById('investmentAmountModal').style.display = 'flex';
            document.getElementById('investmentModal').style.display = 'none';
        }

        function submitInvestmentRequest(event) {
            event.preventDefault();
            
            const formData = new FormData(event.target);
            
            fetch('/investor/request-investment', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                }
            })
            .then(response => {
                closeInvestmentAmountModal();
                setTimeout(() => window.location.reload(), 50);
            });
        }
    </script>
</x-filament-panels::page>
</div>