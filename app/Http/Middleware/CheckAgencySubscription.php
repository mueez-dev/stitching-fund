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
            // Check if user can access features (not locked)
            if (!$user->canAccessFeatures()) {
                // Allow access to subscription page and logout
                $allowedRoutes = ['subscription.show', 'filament.admin.auth.logout', 'filament.admin.auth.login'];
                
                if (!in_array($request->route()->getName(), $allowedRoutes)) {
                    return redirect()->route('subscription.show')
                        ->with('warning', 'Your subscription has expired. Please renew to access this feature.');
                }
            }
        }
        
        return $next($request);
    }
}
