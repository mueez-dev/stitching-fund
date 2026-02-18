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
            $subscriptionState = $user->getSubscriptionState();
            if ($subscriptionState === 'locked') {
                // Allow access to subscription page, logout, dashboard, and root route
                $allowedRoutes = [
                    'subscription.show', 
                    'filament.admin.auth.logout', 
                    'filament.admin.auth.login', 
                    'filament.admin.pages.dashboard',
                    'home'
                ];
                
                // Get current route name safely
                $currentRoute = $request->route();
                $routeName = $currentRoute ? $currentRoute->getName() : null;
                
                // Also allow access to the root path (/) and billing page
                $currentPath = $request->path();
                $allowedPaths = ['/', 'admin/billing'];
                
                // If route is allowed by name or path, continue
                if (($routeName && in_array($routeName, $allowedRoutes)) || in_array($currentPath, $allowedPaths)) {
                    return $next($request);
                }
                
                // Otherwise redirect to billing
                return redirect()->to('/admin/billing')
                    ->with('warning', 'Your subscription has expired. Please renew to access this feature.');
            }
        }
        
        return $next($request);
    }
}
