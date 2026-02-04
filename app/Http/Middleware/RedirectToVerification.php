<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use App\Filament\Pages\Auth\EmailVerification;

class RedirectToVerification
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip for guests and specific pages
        if (!Auth::check() || 
            $request->is('admin/email-verification') || 
            $request->is('admin/login') || 
            $request->is('admin/register') ||
            $request->is('admin/password/*')) {
            return $next($request);
        }

        $user = Auth::user();

        // Redirect if email not verified and verification code exists
        if (is_null($user->email_verified_at) && $user->email_verification_code) {
            // Use the page's static getUrl() method instead of route()
            return redirect(EmailVerification::getUrl());
        }

        return $next($request);
    }
}