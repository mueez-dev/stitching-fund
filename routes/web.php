<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\SubscriptionController;
use App\Filament\Register\Pages\RegisterPage;
use App\Http\Controllers\Demo\DemoRegisterController;



Route::get('/', function () {
    // Get active Agency Owners
    $agencyOwners = App\Models\User::where('role', 'Agency Owner')
                                  ->latest()
                                  ->get(['name', 'email', 'created_at']);
    
    $totalAgencies = App\Models\User::where('role', 'Agency Owner')
                                   ->count();
    
    return view('welcome', compact('agencyOwners', 'totalAgencies'));
})->name('home');

// Add login route alias for Filament
Route::get('/login', function () {
    return redirect('/admin/login');
})->name('login');

// Wallet withdrawal request route
Route::post('/wallet/withdraw-request', [WalletController::class, 'withdrawRequest'])
    ->middleware('auth')
    ->name('wallet.withdraw-request');

// Investment request route for investors
Route::post('/investor/request-investment', [WalletController::class, 'requestInvestment'])
    ->middleware('auth')
    ->name('investor.request-investment');

// Get pool data route
Route::get('/wallet/pool-data', [WalletController::class, 'getPoolData'])
    ->middleware('auth')
    ->name('wallet.pool-data');

// Get available pools for investment
Route::get('/investor/available-pools', [WalletController::class, 'getAvailablePools'])
    ->middleware('auth')
    ->name('investor.available-pools');

   

Route::get('/demo/register', [DemoRegisterController::class, 'show'])
    ->name('demo.register');

Route::post('/demo/register', [DemoRegisterController::class, 'store'])
    ->name('demo.register.store');

// Email verification route (bypass Filament auth)
Route::get('/admin/email-verification', [App\Http\Controllers\EmailVerificationController::class, 'index'])
    ->name('email.verification');

// Email verification submit
Route::post('/admin/email-verification/verify', function (\Illuminate\Http\Request $request) {
    $request->validate([
        'verification_code' => 'required|string|size:6',
    ]);
    
    $email = session('verification_email');
    if (!$email) {
        return redirect()->route('email.verification')
            ->with('error', 'Session expired. Please register again.');
    }
    
    $user = \App\Models\User::where('email', $email)->first();
    if (!$user) {
        return redirect()->route('email.verification')
            ->with('error', 'User not found.');
    }
    
    // Check if code has expired
    if ($user->email_verification_expires_at && $user->email_verification_expires_at < now()) {
        return redirect()->route('email.verification')
            ->with('error', 'Verification code has expired. Please request a new one.');
    }
    
    // Verify the code
    if ($user->email_verification_code && \Illuminate\Support\Facades\Hash::check($request->verification_code, $user->email_verification_code)) {
        // Determine new status based on user role and demo status
        $newStatus = 'active';
        if ($user->role === 'Agency Owner' && !$user->is_demo) {
            $newStatus = 'inactive'; // Keep Agency Owner inactive until subscription
        }
        
        // Mark email as verified
        $user->update([
            'email_verified_at' => now(),
            'email_verification_code' => null,
            'email_verification_expires_at' => null,
            'status' => $newStatus,
        ]);

        // Clear session
        session()->forget('verification_email');

        // Redirect to subscription page for Agency Owners
        if ($user->role === 'Agency Owner' && !$user->is_demo) {
            return redirect()->route('subscription.show')
                ->with('success', 'Email verified! Please complete your subscription.');
        }

        return redirect()->route('filament.admin.auth.login')
            ->with('success', 'Email verified successfully! You can now login.');
    } else {
        return redirect()->route('email.verification')
            ->with('error', 'Invalid verification code.');
    }
})->name('email.verify.submit');

// Email verification resend
Route::post('/admin/email-verification/resend', function () {
    $email = session('verification_email');
    if (!$email) {
        return redirect()->route('email.verification')
            ->with('error', 'Session expired. Please register again.');
    }
    
    $user = \App\Models\User::where('email', $email)->first();
    if (!$user) {
        return redirect()->route('email.verification')
            ->with('error', 'User not found.');
    }
    
    // Generate new 6-digit code
    $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $hashedCode = \Illuminate\Support\Facades\Hash::make($code);

    // Update user with new code
    $user->update([
        'email_verification_code' => $hashedCode,
        'email_verification_expires_at' => now()->addMinutes(15),
    ]);

    // Send new verification email
    try {
        $user->notify(new \App\Notifications\EmailVerificationNotification($code));
        return redirect()->route('email.verification')
            ->with('success', 'New verification code sent to your email.');
    } catch (\Exception $e) {
        return redirect()->route('email.verification')
            ->with('error', 'Could not send verification email. Please try again.');
    }
})->name('email.verify.resend');

// API route for all reviews
Route::get('/api/all-reviews', function () {
    $reviews = \App\Models\Review::where('status', 'approved')
        ->with('user:id,name')
        ->latest()
        ->get()
        ->map(function ($review) {
            return [
                'id' => $review->id,
                'review_text' => $review->review_text,
                'rating' => $review->rating,
                'status' => $review->status,
                'user_name' => $review->user->name ?? 'Anonymous',
                'created_at' => $review->created_at,
            ];
        });
    
    return response()->json(['reviews' => $reviews]);
});

// Subscription routes for Agency Owners
Route::get('/subscription', [SubscriptionController::class, 'showSubscriptionPage'])
    ->name('subscription.show');

Route::post('/subscription/pay', [SubscriptionController::class, 'processPayment'])
    ->name('subscription.pay');

Route::get('/subscription/callback', [SubscriptionController::class, 'paymentCallback'])
    ->name('subscription.callback');

Route::get('/subscription/status', [SubscriptionController::class, 'checkSubscription'])
    ->middleware('auth')
    ->name('subscription.status');


