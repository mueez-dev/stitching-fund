<x-filament-panels::page>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap');

    .billing-wrap * { font-family: 'DM Sans', sans-serif; }
    .billing-wrap h1, .billing-wrap h2, .billing-wrap h3 { font-family: 'Syne', sans-serif; }

    .billing-wrap {
        --gold: #C9A84C;
        --gold-light: #E8C96A;
        --dark: #0D0D0F;
        --card-bg: #13131A;
        --card-border: rgba(201,168,76,0.15);
        --text-muted: #6B6B80;
        background: #0D0D0F;
        min-height: 100vh;
        padding: 2rem;
        border-radius: 1.25rem;
        position: relative;
        overflow: hidden;
    }

    .billing-wrap::before {
        content: '';
        position: absolute;
        top: -200px; right: -200px;
        width: 500px; height: 500px;
        background: radial-gradient(circle, rgba(201,168,76,0.08) 0%, transparent 70%);
        pointer-events: none;
    }

    /* STATUS HERO */
    .status-hero {
        background: linear-gradient(135deg, #13131A 0%, #1A1A26 100%);
        border: 1px solid var(--card-border);
        border-radius: 1.25rem;
        padding: 2.5rem;
        margin-bottom: 1.5rem;
        position: relative;
        overflow: hidden;
    }

    .status-hero::after {
        content: '';
        position: absolute;
        bottom: -60px; right: -60px;
        width: 200px; height: 200px;
        border-radius: 50%;
        border: 1px solid rgba(201,168,76,0.1);
    }

    .status-hero-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        margin-bottom: 2rem;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .status-label {
        font-size: 0.7rem;
        font-weight: 600;
        letter-spacing: 0.2em;
        text-transform: uppercase;
        color: var(--gold);
        margin-bottom: 0.5rem;
    }

    .status-title {
        font-family: 'Syne', sans-serif;
        font-size: 1.75rem;
        font-weight: 800;
        color: #fff;
        margin: 0;
    }

    .status-sub {
        color: var(--text-muted);
        font-size: 0.875rem;
        margin-top: 0.25rem;
    }

    .badge-status {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1.25rem;
        border-radius: 99px;
        font-size: 0.8rem;
        font-weight: 600;
        letter-spacing: 0.05em;
    }

    .badge-active { background: rgba(34,197,94,0.1); color: #4ADE80; border: 1px solid rgba(34,197,94,0.2); }
    .badge-expiring { background: rgba(234,179,8,0.1); color: #FACC15; border: 1px solid rgba(234,179,8,0.2); }
    .badge-grace { background: rgba(249,115,22,0.1); color: #FB923C; border: 1px solid rgba(249,115,22,0.2); }
    .badge-locked { background: rgba(239,68,68,0.1); color: #F87171; border: 1px solid rgba(239,68,68,0.2); }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 1rem;
    }

    .info-card {
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.06);
        border-radius: 0.875rem;
        padding: 1.25rem;
        transition: border-color 0.2s;
    }

    .info-card:hover { border-color: var(--card-border); }

    .info-card-icon {
        font-size: 1.25rem;
        margin-bottom: 0.75rem;
        display: block;
    }

    .info-card-label {
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: var(--text-muted);
        margin-bottom: 0.35rem;
    }

    .info-card-value {
        font-family: 'Syne', sans-serif;
        font-size: 1rem;
        font-weight: 700;
        color: #fff;
    }

    /* ALERT BOX */
    .alert-info {
        background: rgba(201,168,76,0.05);
        border: 1px solid rgba(201,168,76,0.2);
        border-radius: 0.875rem;
        padding: 1rem 1.25rem;
        margin-top: 1.5rem;
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        color: rgba(201,168,76,0.9);
        font-size: 0.85rem;
        line-height: 1.6;
    }

    /* SECTION TITLE */
    .section-title {
        font-family: 'Syne', sans-serif;
        font-size: 1.25rem;
        font-weight: 700;
        color: #fff;
        margin-bottom: 0.35rem;
    }

    .section-sub {
        font-size: 0.8rem;
        color: var(--text-muted);
        margin-bottom: 1.5rem;
    }

    /* PLAN CARDS */
    .plans-section {
        background: #13131A;
        border: 1px solid var(--card-border);
        border-radius: 1.25rem;
        padding: 2.5rem;
        margin-bottom: 1.5rem;
    }

    .plans-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 1.25rem;
    }

    .plan-card {
        background: rgba(255,255,255,0.02);
        border: 1px solid rgba(255,255,255,0.07);
        border-radius: 1rem;
        padding: 1.75rem;
        position: relative;
        transition: all 0.25s;
    }

    .plan-card:hover {
        border-color: rgba(201,168,76,0.3);
        transform: translateY(-3px);
        background: rgba(201,168,76,0.03);
    }

    .plan-card.featured {
        border-color: var(--gold);
        background: linear-gradient(135deg, rgba(201,168,76,0.06) 0%, rgba(201,168,76,0.02) 100%);
    }

    .plan-badge {
        position: absolute;
        top: -12px;
        left: 50%;
        transform: translateX(-50%);
        background: var(--gold);
        color: #000;
        font-size: 0.65rem;
        font-weight: 700;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        padding: 0.3rem 1rem;
        border-radius: 99px;
        white-space: nowrap;
    }

    .plan-name {
        font-family: 'Syne', sans-serif;
        font-size: 1rem;
        font-weight: 700;
        color: #C0C0D0;
        margin-bottom: 1rem;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        font-size: 0.75rem;
    }

    .plan-price {
        font-family: 'Syne', sans-serif;
        font-size: 2.5rem;
        font-weight: 800;
        color: #fff;
        line-height: 1;
        margin-bottom: 0.25rem;
    }

    .plan-card.featured .plan-price { color: var(--gold-light); }

    .plan-period {
        font-size: 0.75rem;
        color: var(--text-muted);
        margin-bottom: 1.5rem;
    }

    .plan-features {
        list-style: none;
        padding: 0;
        margin: 0 0 1.75rem;
        display: flex;
        flex-direction: column;
        gap: 0.6rem;
    }

    .plan-features li {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        font-size: 0.825rem;
        color: #9090A8;
    }

    .plan-features li::before {
        content: '';
        width: 6px; height: 6px;
        border-radius: 50%;
        background: var(--gold);
        flex-shrink: 0;
    }

    .plan-btn {
        width: 100%;
        padding: 0.7rem;
        border-radius: 0.625rem;
        font-size: 0.825rem;
        font-weight: 600;
        cursor: pointer;
        border: none;
        transition: all 0.2s;
        letter-spacing: 0.05em;
    }

    .plan-btn-default {
        background: rgba(255,255,255,0.06);
        color: #9090A8;
        border: 1px solid rgba(255,255,255,0.08);
    }

    .plan-btn-default:hover { background: rgba(255,255,255,0.1); color: #fff; }

    .plan-btn-gold {
        background: linear-gradient(135deg, #C9A84C, #E8C96A);
        color: #000;
        font-weight: 700;
    }

    .plan-btn-gold:hover { 
        background: linear-gradient(135deg, #E8C96A, #C9A84C);
        transform: scale(1.02);
        box-shadow: 0 4px 20px rgba(201,168,76,0.3);
    }

    .plan-btn-purple {
        background: rgba(139,92,246,0.15);
        color: #A78BFA;
        border: 1px solid rgba(139,92,246,0.25);
    }

    .plan-btn-purple:hover { background: rgba(139,92,246,0.25); }

    /* STATS */
    .stats-section {
        background: #13131A;
        border: 1px solid var(--card-border);
        border-radius: 1.25rem;
        padding: 2.5rem;
        margin-bottom: 1.5rem;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 1.25rem;
    }

    .stat-card {
        background: rgba(255,255,255,0.02);
        border: 1px solid rgba(255,255,255,0.06);
        border-radius: 1rem;
        padding: 1.5rem;
        text-align: center;
        transition: border-color 0.2s;
    }

    .stat-card:hover { border-color: rgba(201,168,76,0.2); }

    .stat-number {
        font-family: 'Syne', sans-serif;
        font-size: 2.25rem;
        font-weight: 800;
        color: #fff;
        line-height: 1;
        margin-bottom: 0.35rem;
    }

    .stat-number.blue { color: #60A5FA; }
    .stat-number.green { color: #4ADE80; }
    .stat-number.purple { color: #A78BFA; }
    .stat-number.gold { color: var(--gold-light); }

    .stat-label { font-size: 0.75rem; color: var(--text-muted); margin-bottom: 1rem; }

    .stat-bar {
        width: 100%;
        height: 3px;
        background: rgba(255,255,255,0.06);
        border-radius: 99px;
        overflow: hidden;
    }

    .stat-bar-fill { height: 100%; border-radius: 99px; transition: width 0.8s ease; }
    .stat-bar-fill.blue { background: linear-gradient(90deg, #3B82F6, #60A5FA); }
    .stat-bar-fill.purple { background: linear-gradient(90deg, #7C3AED, #A78BFA); }

    /* PAYMENT */
    .payment-section {
        background: #13131A;
        border: 1px solid var(--card-border);
        border-radius: 1.25rem;
        padding: 2.5rem;
        margin-bottom: 1.5rem;
    }

    .payment-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1.5rem;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .add-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.6rem 1.25rem;
        background: rgba(201,168,76,0.1);
        border: 1px solid rgba(201,168,76,0.25);
        color: var(--gold-light);
        border-radius: 0.625rem;
        font-size: 0.8rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        font-family: 'DM Sans', sans-serif;
    }

    .add-btn:hover { background: rgba(201,168,76,0.18); }

    .payment-card {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1.25rem;
        background: rgba(255,255,255,0.02);
        border: 1px solid rgba(255,255,255,0.06);
        border-radius: 0.875rem;
        margin-bottom: 0.875rem;
        transition: border-color 0.2s;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .payment-card:hover { border-color: rgba(201,168,76,0.2); }

    .payment-card-left { display: flex; align-items: center; gap: 1rem; }

    .payment-icon {
        width: 44px; height: 44px;
        background: rgba(255,255,255,0.04);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 0.625rem;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.2rem;
    }

    .payment-name { font-size: 0.875rem; font-weight: 600; color: #E0E0F0; margin-bottom: 0.2rem; }
    .payment-meta { font-size: 0.75rem; color: var(--text-muted); }

    .default-badge {
        display: inline-block;
        background: rgba(74,222,128,0.1);
        color: #4ADE80;
        border: 1px solid rgba(74,222,128,0.2);
        font-size: 0.65rem;
        font-weight: 600;
        padding: 0.15rem 0.5rem;
        border-radius: 99px;
        margin-left: 0.5rem;
        letter-spacing: 0.05em;
        text-transform: uppercase;
    }

    .payment-actions { display: flex; gap: 0.75rem; }
    .payment-actions button { font-size: 0.75rem; font-weight: 500; cursor: pointer; border: none; background: none; font-family: 'DM Sans', sans-serif; }
    .btn-edit { color: #60A5FA; }
    .btn-edit:hover { color: #93C5FD; }
    .btn-remove { color: #F87171; }
    .btn-remove:hover { color: #FCA5A5; }

    /* QUICK ACTIONS */
    .actions-section {
        background: #13131A;
        border: 1px solid var(--card-border);
        border-radius: 1.25rem;
        padding: 2.5rem;
    }

    .actions-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 1rem;
    }

    .action-card {
        background: rgba(255,255,255,0.02);
        border: 1px solid rgba(255,255,255,0.06);
        border-radius: 1rem;
        padding: 1.5rem 1.25rem;
        cursor: pointer;
        transition: all 0.25s;
        text-align: center;
        border: none;
        width: 100%;
        font-family: 'DM Sans', sans-serif;
    }

    .action-card:hover { transform: translateY(-2px); }

    .action-card.renew { background: rgba(239,68,68,0.06); border: 1px solid rgba(239,68,68,0.15); }
    .action-card.renew:hover { background: rgba(239,68,68,0.1); border-color: rgba(239,68,68,0.3); }

    .action-card.history { background: rgba(96,165,250,0.06); border: 1px solid rgba(96,165,250,0.15); }
    .action-card.history:hover { background: rgba(96,165,250,0.1); border-color: rgba(96,165,250,0.3); }

    .action-card.invoice { background: rgba(74,222,128,0.06); border: 1px solid rgba(74,222,128,0.15); }
    .action-card.invoice:hover { background: rgba(74,222,128,0.1); border-color: rgba(74,222,128,0.3); }

    .action-card.settings { background: rgba(167,139,250,0.06); border: 1px solid rgba(167,139,250,0.15); }
    .action-card.settings:hover { background: rgba(167,139,250,0.1); border-color: rgba(167,139,250,0.3); }

    .action-icon {
        font-size: 1.75rem;
        display: block;
        margin-bottom: 0.75rem;
    }

    .action-name {
        font-family: 'Syne', sans-serif;
        font-size: 0.825rem;
        font-weight: 700;
        color: #E0E0F0;
        margin-bottom: 0.25rem;
    }

    .action-desc { font-size: 0.7rem; color: var(--text-muted); }
</style>

<div class="billing-wrap">

    {{-- ═══════════════════════════════════════ --}}
    {{-- STATUS HERO --}}
    {{-- ═══════════════════════════════════════ --}}
    <div class="status-hero">
        <div class="status-hero-top">
            <div>
                <div class="status-label">⬡ Billing & Subscription</div>
                <h1 class="status-title">Subscription Status</h1>
                <p class="status-sub">Manage your plan, billing, and account access</p>
            </div>
            <div>
                {!! $this->getSubscriptionStatusBadge() !!}
            </div>
        </div>

        <div class="info-grid">
            <div class="info-card">
                <span class="info-card-icon">📅</span>
                <div class="info-card-label">Expires On</div>
                <div class="info-card-value">
                    {{ $this->user->subscription_expires_at ? $this->user->subscription_expires_at->format('M d, Y') : 'Never' }}
                </div>
            </div>

            <div class="info-card">
                <span class="info-card-icon">👤</span>
                <div class="info-card-label">Account Type</div>
                <div class="info-card-value">{{ $this->user->role ?? 'Standard' }}</div>
            </div>

            @if($this->getSubscriptionState() === 'expired_grace')
            <div class="info-card">
                <span class="info-card-icon">⏳</span>
                <div class="info-card-label">Grace Period Left</div>
                <div class="info-card-value" style="color: #FB923C;">
                    {{ $this->user->getGraceTimeRemaining()['ui_format'] }}
                </div>
            </div>
            @endif

            <div class="info-card">
                <span class="info-card-icon">⚡</span>
                <div class="info-card-label">Days Until Expiry</div>
                <div class="info-card-value" style="color: {{ $this->getDaysUntilExpiry() <= 7 ? '#F87171' : '#C9A84C' }};">
                    {{ $this->getDaysUntilExpiry() }} days
                </div>
            </div>
        </div>

        <div class="alert-info">
            <span>ℹ️</span>
            <span>{{ $this->getSubscriptionDetails() }}</span>
        </div>
    </div>

   {{-- PLANS --}}
<div class="plans-section">
    <div class="section-title">Choose Your Plan</div>
    <div class="section-sub">
        @if($this->getSubscriptionState() === 'locked')
            ⚠️ Your account is locked. Renew to restore full access.
        @elseif($this->getSubscriptionState() === 'expired_grace')
            ⚠️ Grace period active. Renew before access is locked.
        @elseif($this->getSubscriptionState() === 'expiring')
            Your subscription expires soon. Renew now to extend from your current expiry date.
        @else
            Upgrade anytime — changes take effect immediately
        @endif
    </div>

    <div class="plans-grid">
        {{-- Starter --}}
        <div class="plan-card">
            <div class="plan-name">Starter</div>
            <div class="plan-price">$29</div>
            <div class="plan-period">per month</div>
            <ul class="plan-features">
                <li>Up to 10 investors</li>
                <li>Basic reporting</li>
                <li>Email support</li>
            </ul>
            <button class="plan-btn plan-btn-default" disabled>Current Plan</button>
        </div>

        {{-- Professional --}}
        <div class="plan-card featured">
            <div class="plan-badge">⭐ Most Popular</div>
            <div class="plan-name">Professional</div>
            <div class="plan-price">PKR 3,000</div>
            <div class="plan-period">per 30 days</div>
            <ul class="plan-features">
                <li>Up to 50 investors</li>
                <li>Advanced analytics</li>
                <li>Priority support</li>
                <li>Custom branding</li>
            </ul>

            @if($this->getSubscriptionState() === 'active')
                <button class="plan-btn plan-btn-default" disabled>✓ Currently Active</button>
            @elseif($this->getSubscriptionState() === 'expiring')
                <button class="plan-btn plan-btn-gold" wire:click="renewSubscription" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="renewSubscription">🔄 Extend Subscription</span>
                    <span wire:loading wire:target="renewSubscription">Processing...</span>
                </button>
                <p style="font-size:0.7rem; color:#C9A84C; text-align:center; margin-top:0.5rem;">
                    Will extend from your expiry date
                </p>
            @elseif($this->getSubscriptionState() === 'expired_grace')
                <button class="plan-btn plan-btn-gold" wire:click="renewSubscription" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="renewSubscription">⚡ Renew Now</span>
                    <span wire:loading wire:target="renewSubscription">Processing...</span>
                </button>
                <p style="font-size:0.7rem; color:#FB923C; text-align:center; margin-top:0.5rem;">
                    30 days from today
                </p>
            @elseif($this->getSubscriptionState() === 'locked')
                <button class="plan-btn plan-btn-gold" wire:click="renewSubscription" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="renewSubscription">🔓 Unlock Account</span>
                    <span wire:loading wire:target="renewSubscription">Processing...</span>
                </button>
                <p style="font-size:0.7rem; color:#F87171; text-align:center; margin-top:0.5rem;">
                    30 days from today
                </p>
            @endif
        </div>

        {{-- Enterprise --}}
        <div class="plan-card">
            <div class="plan-name">Enterprise</div>
            <div class="plan-price">Custom</div>
            <div class="plan-period">contact us</div>
            <ul class="plan-features">
                <li>Unlimited investors</li>
                <li>Custom features</li>
                <li>Dedicated support</li>
                <li>API access</li>
            </ul>
            <button class="plan-btn plan-btn-purple" wire:click="manageBilling">Contact Sales</button>
        </div>
    </div>
</div>

   {{-- QUICK ACTIONS --}}
<div class="actions-section">
    <div class="section-title">Quick Actions</div>
    <div class="section-sub">Shortcuts to manage your account</div>

    <div class="actions-grid">
        @if(in_array($this->getSubscriptionState(), ['expiring', 'expired_grace', 'locked']))
        <button class="action-card renew" wire:click="renewSubscription" wire:loading.attr="disabled">
            <span class="action-icon">
                @if($this->getSubscriptionState() === 'locked') 🔓
                @elseif($this->getSubscriptionState() === 'expiring') 🔄
                @else ⚡
                @endif
            </span>
            <div class="action-name">
                @if($this->getSubscriptionState() === 'locked') Unlock Account
                @elseif($this->getSubscriptionState() === 'expiring') Extend Plan
                @else Renew Now
                @endif
            </div>
            <div class="action-desc">
                @if($this->getSubscriptionState() === 'locked') Restore full access
                @elseif($this->getSubscriptionState() === 'expiring') Extend from expiry date
                @else 30 days from today
                @endif
            </div>
        </button>
        @endif

        <button class="action-card history" wire:click="viewHistory">
            <span class="action-icon">📋</span>
            <div class="action-name">Payment History</div>
            <div class="action-desc">View transactions</div>
        </button>

        <button class="action-card invoice" wire:click="downloadInvoices">
            <span class="action-icon">📥</span>
            <div class="action-name">Invoices</div>
            <div class="action-desc">Download PDF copies</div>
        </button>
    </div>
</div>

</div>

</x-filament-panels::page>