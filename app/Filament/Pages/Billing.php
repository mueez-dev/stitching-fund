<?php

namespace App\Filament\Pages;

use BackedEnum;
use Carbon\Carbon;
use Stripe\Stripe;
use App\Models\User;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use Stripe\Checkout\Session as StripeSession;

class Billing extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';
    protected static ?int $navigationSort = 100;
    protected string $view = 'filament.pages.billing';
    public ?User $user;

    public function mount(): void
    {
        $this->user = Auth::user();
    }

    public function getTitle(): string
    {
        return 'Billing & Subscription';
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();
        if (!$user) return false;
        if ($user->role === 'Super Admin') return false;
        return true;
    }

    public function getSubscriptionState(): string
    {
        return $this->user->getSubscriptionState();
    }

    public function getSubscriptionStatusBadge(): string
    {
        $state = $this->getSubscriptionState();
        return match($state) {
            'active'        => '<span class="badge-status badge-active">● Active</span>',
            'expiring'      => '<span class="badge-status badge-expiring">● Expiring Soon</span>',
            'expired_grace' => '<span class="badge-status badge-grace">● Grace Period</span>',
            'locked'        => '<span class="badge-status badge-locked">● Locked</span>',
            default         => '<span class="badge-status">● Unknown</span>',
        };
    }

    public function getSubscriptionDetails(): string
    {
        $state       = $this->getSubscriptionState();
        $graceEndsAt = $this->user->getGraceEndsAt();
        $expiresAt   = $this->user->subscription_expires_at;

        return match($state) {
            'active'        => "Your subscription is active. Next billing date: {$expiresAt->format('M d, Y')}.",
            'expiring'      => "Your subscription will expire on {$expiresAt->format('M d, Y')}. Renew now to avoid interruption.",
            'expired_grace' => "Subscription expired on {$expiresAt->format('M d, Y')}. Grace period ends {$graceEndsAt->format('M d, Y')}. Renew immediately.",
            'locked'        => "Account locked. Subscription expired on {$expiresAt->format('M d, Y')}. Please renew to restore access.",
            default         => 'Subscription status unknown.'
        };
    }

    public function getDaysUntilExpiry(): int
    {
        if (!$this->user->subscription_expires_at) return 0;
        return max(0, (int) now()->diffInDays($this->user->subscription_expires_at, false));
    }

    public function getUsageStats(): array
    {
        return [
            'investors_count'   => 3,
            'investors_limit'   => 10,
            'reports_generated' => 12,
            'storage_used'      => '2.3 GB',
            'storage_limit'     => '10 GB'
        ];
    }

    public function renewSubscription(): void
    {
        Log::info('Renewal button clicked', ['user_id' => $this->user->id, 'user_email' => $this->user->email]);
        
        $user  = $this->user;
        $state = $user->getSubscriptionState();
        
        Log::info('User subscription state', ['state' => $state, 'expiry' => $user->subscription_expires_at]);

        // Expiring/Active → expiry date se extend
        // Locked/Grace → aaj se extend
        if (in_array($state, ['expiring', 'active'])) {
            $baseDate = $user->subscription_expires_at->copy();
        } else {
            $baseDate = now();
        }

        $newExpiry = $baseDate->copy()->addDays(30);

        try {
            Stripe::setApiKey(config('services.stripe.secret'));

            // Increase timeout settings
            $client = new \Stripe\HttpClient\CurlClient();
            $client->setTimeout(120); // 120 seconds
            $client->setConnectTimeout(30); // 30 seconds
            \Stripe\ApiRequestor::setHttpClient($client);

            Log::info('Creating Stripe session', ['user_id' => $user->id, 'new_expiry' => $newExpiry]);

            $checkoutSession = StripeSession::create([
                'payment_method_types' => ['card'],
                'line_items'           => [[
                    'price_data' => [
                        'currency'     => 'pkr',
                        'product_data' => [
                            'name'        => 'Agency Owner Plan - Renewal',
                            'description' => '30 days subscription renewal',
                        ],
                        'unit_amount'  => 300000,
                    ],
                    'quantity'   => 1,
                ]],
                'mode'        => 'payment',
                'success_url' => config('app.url') . route('subscription.callback', [], false) . '?session_id=' . '{CHECKOUT_SESSION_ID}',
                'metadata'    => [
                    'user_id'    => $user->id,
                    'user_email' => $user->email,
                    'new_expiry' => $newExpiry->format('Y-m-d H:i:s'),
                    'plan_type'  => 'Agency Owner Plan - Renewal',
                    'is_renewal' => 'true',
                ],
            ]);

            Log::info('Stripe session created', ['session_id' => $checkoutSession->id, 'url' => $checkoutSession->url]);

            $this->redirect($checkoutSession->url);

        } catch (\Exception $e) {
            Log::error('Renewal Stripe error', [
                'user_id' => $user->id,
                'error'   => $e->getMessage()
            ]);

            Notification::make()
                ->title('Payment Error')
                ->body('Error: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function viewHistory(): void
    {
        $user = $this->user;
        $payments = \App\Models\Payment::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();
            
        $paymentList = $payments->map(function($payment) {
            return [
                'date' => $payment->created_at->format('M d, Y'),
                'amount' => 'PKR ' . number_format($payment->amount, 2),
                'status' => ucfirst($payment->status),
                'method' => ucfirst($payment->payment_method),
                'description' => $payment->metadata['plan_type'] ?? 'Subscription Payment'
            ];
        });
        
        if ($paymentList->isEmpty()) {
            Notification::make()
                ->title('Payment History')
                ->body('No payment transactions found.')
                ->info()
                ->send();
        } else {
            $message = "Recent Payments:\n\n";
            foreach ($paymentList->take(5) as $payment) {
                $message .= "📅 {$payment['date']} - {$payment['amount']}\n";
                $message .= "   {$payment['description']} ({$payment['status']})\n\n";
            }
            
            Notification::make()
                ->title('Payment History')
                ->body($message)
                ->info()
                ->send();
        }
    }

    public function downloadInvoices(): void
    {
        $user = $this->user;
        $payments = \App\Models\Payment::where('user_id', $user->id)
            ->where('status', 'completed')
            ->orderBy('created_at', 'desc')
            ->get();
            
        if ($payments->isEmpty()) {
            Notification::make()
                ->title('No Invoices')
                ->body('No completed payments found to generate invoices.')
                ->warning()
                ->send();
        } else {
            $invoiceList = "Available Invoices:\n\n";
            foreach ($payments as $payment) {
                $invoiceList .= "🧾 Invoice #{$payment->id}\n";
                $invoiceList .= "   Date: {$payment->created_at->format('M d, Y')}\n";
                $invoiceList .= "   Amount: PKR " . number_format($payment->amount, 2) . "\n";
                $invoiceList .= "   Status: {$payment->status}\n\n";
            }
            
            Notification::make()
                ->title('Invoices Ready')
                ->body($invoiceList . 'PDF download feature will be available soon.')
                ->success()
                ->send();
        }
    }

}