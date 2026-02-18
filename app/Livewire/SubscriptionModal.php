<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;

class SubscriptionModal extends Component
{
    public string $state = 'active';
    public int $daysLeft = 0;
    public string $timeRemaining = '';
    public ?Carbon $expiresAt = null;
    public ?Carbon $graceEndsAt = null;

    public function calculateTimeRemaining(Carbon $endDate): string
    {
        $now = now();
        $diff = $now->diff($endDate);
        
        if ($diff->days > 0) {
            return $diff->days . ' days ' . $diff->h . ' hours';
        } elseif ($diff->h > 0) {
            return $diff->h . ' hours ' . $diff->i . ' minutes';
        } else {
            return $diff->i . ' minutes';
        }
    }

    public function mount()
    {
        $user = Auth::user();
        
        if (!$user || $user->role === 'Super Admin') {
            return;
        }

        $this->state = $user->getSubscriptionState();

        if ($this->state === 'active') {
            return;
        }

        if ($user->subscription_expires_at) {
            $this->expiresAt = $user->subscription_expires_at;
            $this->graceEndsAt = $user->subscription_expires_at->copy()->addHours(72);
        }

        if ($this->state === 'expiring' && $this->expiresAt) {
            $this->daysLeft = (int) now()->diffInDays($this->expiresAt);
            if (!Session::has('expiring_popup_shown')) {
                Session::put('expiring_popup_shown', true);
                $this->dispatch('open-modal', id: 'subscription-modal');
            }
        }

        if ($this->state === 'expired_grace' && $this->graceEndsAt) {
            $this->timeRemaining = $this->calculateTimeRemaining($this->graceEndsAt);
            if (!Session::has('grace_popup_login_shown')) {
                Session::put('grace_popup_login_shown', true);
                $this->dispatch('open-modal', id: 'subscription-modal');
            }
        }

        if ($this->state === 'locked') {
            $this->daysLeft = 0;
            if (!Session::has('locked_popup_shown')) {
                Session::put('locked_popup_shown', true);
                $this->dispatch('open-modal', id: 'subscription-modal');
            }
        }
    }

    public function renew()
    {
        Session::put('subscription_popup_dismissed', true);
        $this->dispatch('close-modal', id: 'subscription-modal');
        $this->redirect(route('filament.admin.pages.billing'));
    }

    public function close()
    {
        $this->dispatch('close-modal', id: 'subscription-modal');
    }

  protected $listeners = []; // No listeners needed

public function render()
{
    return view('livewire.subscription-modal');
}
}

 

