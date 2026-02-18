<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;

class SubscriptionModal extends Component
{
    public string $state;
    public int $daysLeft;
    public Carbon $expiresAt;
    public Carbon $graceEndsAt;

    public function mount()
    {
        $user = Auth::user();
        
        if (!$user) {
            return;
        }

        // Don't show subscription modal to Super Admin
        if ($user->role === 'Super Admin') {
            return;
        }

        $this->expiresAt = $user->subscription_expires_at;
        $this->graceEndsAt = $user->subscription_expires_at->addDays(3);

        $this->state = $user->getSubscriptionState();

        if ($this->state === 'expiring') {
            $this->daysLeft = (int) now()->diffInDays($this->expiresAt);
            
            // Only show once per login session for expiring period
            if (!Session::has('expiring_popup_shown')) {
                Session::put('expiring_popup_shown', true);
                $this->dispatch('open-modal', id: 'subscription-modal');
            }
        }

        if ($this->state === 'expired_grace') {
            $this->daysLeft = max(0, (int) now()->diffInDays($this->graceEndsAt));
            
            // Only show on login for grace period, not on page reload
            if (!Session::has('grace_popup_login_shown')) {
                Session::put('grace_popup_login_shown', true);
                $this->dispatch('open-modal', id: 'subscription-modal');
            }
        }
    }

    public function renew()
    {
        // Set session to prevent popup from showing again after renew
        Session::put('subscription_popup_dismissed', true);
        
        // Close modal first
        $this->dispatch('close-modal', id: 'subscription-modal');
        
        // Redirect to billing page using window.location
        $this->js('window.location.href = "' . route('filament.admin.pages.billing') . '";');
    }

    public function close()
    {
        // Close the modal
        $this->dispatch('close-modal', id: 'subscription-modal');
    }

    public function render()
    {
        return view('livewire.subscription-modal');
    }
}
