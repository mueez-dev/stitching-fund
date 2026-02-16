<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use BackedEnum;

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
        return true; // Always accessible
    }
    
    public function getSubscriptionState(): string
    {
        return $this->user->getSubscriptionState();
    }
    
    public function getSubscriptionStatusBadge(): string
    {
        $state = $this->getSubscriptionState();
        
        return match($state) {
            'active' => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Active</span>',
            'expiring' => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Expiring Soon</span>',
            'expired_grace' => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Grace Period</span>',
            'locked' => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Locked</span>',
            default => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Unknown</span>',
        };
    }
    
    public function getSubscriptionDetails(): string
    {
        $state = $this->getSubscriptionState();
        $graceEndsAt = $this->user->getGraceEndsAt();
        
        return match($state) {
            'active' => 'Your subscription is active and all features are available.',
            'expiring' => 'Your subscription will expire soon. Please renew to avoid interruption.',
            'expired_grace' => "Your subscription has expired. Grace period ends on {$graceEndsAt->format('M d, Y')}. Read-only access enabled.",
            'locked' => 'Your subscription has expired and account is locked. Please renew to restore access.',
            default => 'Subscription status unknown.'
        };
    }
    
    public function renewSubscription(): void
    {
        // TODO: Implement Stripe Checkout
        Notification::make()
            ->title('Coming Soon')
            ->body('Stripe integration will be available soon.')
            ->info()
            ->send();
    }
    
    public function viewHistory(): void
    {
        // TODO: Implement payment history
        Notification::make()
            ->title('Coming Soon')
            ->body('Payment history will be available soon.')
            ->info()
            ->send();
    }
}
