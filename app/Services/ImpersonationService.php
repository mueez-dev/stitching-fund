<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class ImpersonationService
{
    /**
     * Stop impersonation and return to original user.
     */
    public static function stop(): void
    {
        if (Session::has('impersonator_id')) {
            $originalUserId = Session::get('impersonator_id');
            
            // Log out current impersonated user
            Auth::logout();
            
            // Log back in as original user
            Auth::loginUsingId($originalUserId);
            
            // Clear impersonation session data
            Session::forget('impersonator_id');
            Session::forget('impersonating');
        }
    }
    
    /**
     * Start impersonating a user.
     */
    public static function start(int $userId): bool
    {
        if (!Auth::check()) {
            return false;
        }
        
        // Store original user ID
        Session::put('impersonator_id', Auth::id());
        Session::put('impersonating', true);
        
        // Log in as target user
        Auth::loginUsingId($userId);
        
        return true;
    }
    
    /**
     * Check if current session is impersonating.
     */
    public static function isImpersonating(): bool
    {
        return Session::has('impersonator_id');
    }
    
    /**
     * Get the original user ID.
     */
    public static function getOriginalUserId(): ?int
    {
        return Session::get('impersonator_id');
    }
}
