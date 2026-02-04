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
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip middleware for auth pages and verification page
        if ($request->is('admin/login') || 
            $request->is('admin/register') || 
            $request->is('admin/email-verification') ||
            $request->is('admin/password/*')) {
            return $next($request);
        }

        if (Auth::check()) {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            
            // Skip status check if user needs email verification
            if (is_null($user->email_verified_at) && $user->email_verification_code) {
                return $next($request);
            }
            
            // Send login notification only once per session
            if (!session()->has('login_notification_sent')) {
                Notification::make()
                    ->title('Welcome back!')
                    ->body('You have successfully logged in to the dashboard')
                    ->success()
                    ->sendToDatabase($user);
                session()->put('login_notification_sent', true);
            }
            
            // Send demo warning notification for demo users
            if ($user->is_demo && !session()->has('demo_warning_sent')) {
                Notification::make()
                    ->title('⚠️ Important - Demo Account Warning!')
                    ->body('Please do not add real data in your trial account. The trial is only for checking system functionality. When your trial expires, all your data will be deleted.')
                    ->warning()
                    ->persistent()
                    ->sendToDatabase($user);
                session()->put('demo_warning_sent', true);
            }
            
            // Check for demo user and handle expiry
            if ($user->is_demo && $user->demo_expires_at) {
                $expiresAt = \Carbon\Carbon::parse($user->demo_expires_at);
                $now = now();
                $daysLeft = $now->diffInDays($expiresAt, false); // false for signed value
                
                // If account has expired, clean up data and block access
                if ($daysLeft < 0) {
                    // Clean up all demo data (only once per user)
                    if (!session()->has('demo_data_cleaned_' . $user->id)) {
                        try {
                            (new \App\Services\Demo\DemoDataCleanupService())->cleanupExpiredDemoUser($user);
                            session()->put('demo_data_cleaned_' . $user->id, true);
                        } catch (\Exception $e) {
                            Log::error('Failed to cleanup demo data', [
                                'user_id' => $user->id,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                    
                    // Send expired notification
                    if (!session()->has('demo_expired_notification_sent')) {
                        Notification::make()
                            ->title('Demo Account Expired')
                            ->body('Your demo account has expired. Please upgrade to continue using our services.')
                            ->danger()
                            ->sendToDatabase($user);
                        session()->put('demo_expired_notification_sent', true);
                    }
                    
                    // Logout the user
                    Auth::logout();
                    
                    // Redirect to login
                    return redirect()->route('filament.admin.auth.login');
                }
                
                // Send expiry notification if account expires in 3 days or less
                elseif ($daysLeft <= 3 && !session()->has('demo_expiry_notification_sent')) {
                    if ($daysLeft < 0) {
                        // Account has expired - use absolute value and round down
                        $daysAgo = abs(floor($daysLeft));
                        $message = "Your demo account expired {$daysAgo} day(s) ago on {$expiresAt->format('M d, Y')}. Please upgrade your account to continue using our services.";
                    } else {
                        // Account will expire soon - round down to whole days
                        $daysRemaining = floor($daysLeft);
                        $message = "Your demo account will expire in {$daysRemaining} day(s) on {$expiresAt->format('M d, Y')}. Please upgrade your account to continue using our services.";
                    }
                    
                    Notification::make()
                        ->title('Demo Account Expiry Reminder')
                        ->body($message)
                        ->warning()
                        ->sendToDatabase($user);
                    session()->put('demo_expiry_notification_sent', true);
                }
            }
            
            // Check if user is inactive
            if ($user->status === 'inactive') {
                // Send notification using Filament's notification system
                Notification::make()
                    ->title('Your account is inactive')
                    ->body('Please contact admin')
                    ->danger()
                    ->send();
                
                // Logout the user
                Auth::logout();
                
                // Redirect to login
                return redirect()->route('filament.admin.auth.login');
            }
            
            // Check if user account has expired
            if ($user->status === 'expired') {
                // Send notification using Filament's notification system
                Notification::make()
                    ->title('Your account has expired')
                    ->body('Your demo account has expired. Please upgrade to continue using our services.')
                    ->danger()
                    ->send();
                
                // Logout the user
                Auth::logout();
                
                // Redirect to login
                return redirect()->route('filament.admin.auth.login');
            }
        }

        return $next($request);
    }
}