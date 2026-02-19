<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Filament\Notifications\Notification;

class CheckUserStatus
{
    // ✅ Sirf yeh pages grace period mein kaam karen ge
    protected array $graceAllowed = ['lots', 'reviews', 'contacts', 'billings', 'billing', 'dashboard'];

    public function handle(Request $request, Closure $next): Response
    {
         if ($request->is('livewire/update') || $request->is('livewire/*')) {
        return $next($request);
    }
    
        if (
            $request->is('admin/login') ||
            $request->is('admin/register') ||
            $request->is('admin/email-verification') ||
            $request->is('admin/password/*')
        ) {
            return $next($request);
        }

        if (!Auth::check()) return $next($request);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (is_null($user->email_verified_at) && $user->email_verification_code) {
            return $next($request);
        }

        // Demo expiry
        if ($user->is_demo && $user->demo_expires_at) {
            $daysLeft = now()->diffInDays(now()->parse($user->demo_expires_at), false);
            if ($daysLeft < 0) {
                if (!session()->has('demo_data_cleaned_' . $user->id)) {
                    try {
                        (new \App\Services\Demo\DemoDataCleanupService())->cleanupExpiredDemoUser($user);
                        session()->put('demo_data_cleaned_' . $user->id, true);
                    } catch (\Throwable $e) {
                        Log::error('Demo cleanup failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
                    }
                }
                Auth::logout();
                return $this->safeRedirect('filament.admin.auth.login');
            }
        }

        // Inactive/expired
        if (in_array($user->status, ['inactive', 'expired'], true)) {
            Notification::make()->title('Account Disabled')->body('Please contact support.')->danger()->send();
            Auth::logout();
            return $this->safeRedirect('filament.admin.auth.login');
        }

        $state = $user->getSubscriptionState();

        if ($request->is('admin/billing*')) return $next($request);

        switch ($state) {

         case 'locked':
    if (!$request->routeIs('filament.admin.pages.billing') && 
        !$request->is('admin/billing*') &&
        !$request->is('admin') &&
        !$request->routeIs('filament.admin.pages.dashboard') &&
        !$request->routeIs('subscription.callback') &&  // ← YEH ADD KARO
        !$request->is('subscription/callback*')) {       // ← YEH BHI ADD KARO
        
        if (!session()->has('locked_notification_sent')) {
            Notification::make()->title('Account Locked')->body('Your subscription has expired. Please renew to continue.')->danger()->send();
            session()->put('locked_notification_sent', true);
        }
        return $this->safeRedirect('filament.admin.pages.billing');
    }
    break;

            case 'expired_grace':
                // Let JavaScript handle all notifications - no middleware interference
                break;

            case 'expiring':
                if (!session()->has('expiring_notification_sent')) {
                    $daysLeft = (int) $user->subscription_expires_at->diffInDays(now());
                    Notification::make()->title('Subscription Expiring')->body("Your subscription expires in {$daysLeft} day(s).")->warning()->send();
                    session()->put('expiring_notification_sent', true);
                }
                break;
        }

        return $next($request);
    }

    private function safeRedirect(string $route)
    {
        return redirect()->route($route)->setStatusCode(303);
    }
}