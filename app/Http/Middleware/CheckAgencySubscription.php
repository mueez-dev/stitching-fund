<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckAgencySubscription
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        
        // Only check subscription for Agency Owners
        if ($user && $user->role === 'Agency Owner') {
            // Check if user has active subscription
            if (!$this->hasActiveSubscription($user)) {
                // Redirect to subscription page
                return redirect()->route('subscription.show')
                    ->with('warning', 'You need an active subscription to access this feature.');
            }
        }
        
        return $next($request);
    }
    
    /**
     * Check if user has active subscription
     */
    private function hasActiveSubscription($user): bool
    {
        return $user->subscription_status === 'active' && 
               $user->subscription_expires_at && 
               $user->subscription_expires_at > now();
    }
}
