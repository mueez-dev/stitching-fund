<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use App\Models\User;
use App\Models\Payment;

class SubscriptionController extends Controller
{
    /**
     * Display subscription purchase page
     */
    public function showSubscriptionPage()
    {
        $user = Auth::user();
        
         if ($user && $user->subscription_status === 'active' && 
        $user->subscription_expires_at && 
        $user->subscription_expires_at > now()) {
        return redirect()->route('filament.admin.pages.dashboard');
    }
        // Show subscription page for payment
        return view('subscription.index', [
            'user' => $user,
            'planPrice' => 3000,
            'planName' => 'Agency Owner Plan',
            'planDuration' => '30 days'
        ]);
    }
    
    /**
     * Process subscription payment
     */
    public function processPayment(Request $request)
    {
        // Check if user is authenticated
        $user = Auth::user();
        
        // Validate request
        $request->validate([
            'payment_method' => 'required|string',
            'email' => $user ? 'sometimes|email' : 'required|email',
            'name' => $user ? 'sometimes|string|max:255' : 'required|string|max:255'
        ]);
        
        // For authenticated users, use their info
        if ($user) {
            $email = $user->email;
            $name = $user->name;
        } else {
            // For new users, use form data
            $email = $request->email;
            $name = $request->name;
        }
        
        // Store user info in session for account creation after payment (only for new users)
        if (!$user) {
            session([
                'pending_user_email' => $email,
                'pending_user_name' => $name,
                'pending_subscription' => true
            ]);
        }
        
        try {
            $paymentMethod = $request->payment_method;
            
            if ($paymentMethod === 'stripe') {
                // Check if Stripe secret key is configured
                $stripeSecret = env('STRIPE_SECRET');
                if (!$stripeSecret) {
                    Log::error('Stripe secret key not configured');
                    return response()->json([
                        'success' => false,
                        'message' => 'Payment gateway not configured. Please contact support.'
                    ], 500);
                }
                
                // Set your Stripe secret key
                Stripe::setApiKey($stripeSecret);
                
                try {
                    $session = Session::create([
                        'payment_method_types' => ['card'],
                        'line_items' => [[
                            'price_data' => [
                                'currency' => 'pkr',
                                'product_data' => [
                                    'name' => 'Agency Owner Plan',
                                    'description' => '30 days subscription',
                                ],
                                'unit_amount' => 300000, // PKR 3,000 (in paisa)
                            ],
                            'quantity' => 1,
                        ]],
                        'mode' => 'payment',
                        'success_url' => route('subscription.callback') . '?session_id={CHECKOUT_SESSION_ID}',
                        'cancel_url' => route('subscription.show'),
                        'metadata' => [
                            'plan' => 'Agency Owner Plan',
                            'user_email' => $email,
                            'is_new_user' => $user ? 'false' : 'true'
                        ]
                    ]);
                    
                    Log::info('Stripe session created successfully', ['session_id' => $session->id]);
                    
                    return response()->json([
                        'success' => true,
                        'redirect_url' => $session->url
                    ]);
                    
                } catch (\Stripe\Exception\ApiErrorException $e) {
                    Log::error('Stripe API error', [
                        'error' => $e->getMessage(),
                        'code' => $e->getStripeCode()
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'Payment gateway error: ' . $e->getMessage()
                    ], 500);
                }
            }
            
            
        } catch (\Exception $e) {
            Log::error('Subscription payment failed', [
                'user_id' => $user ? $user->id : null,
                'email' => $email,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Payment failed. Please try again.'
            ], 500);
        }
    }
    
    /**
     * Handle payment callback from Stripe
     */
    public function paymentCallback(Request $request)
    {
        $sessionId = $request->get('session_id');
        
        if ($sessionId) {
            Stripe::setApiKey(env('STRIPE_SECRET'));
            
            try {
                $session = Session::retrieve($sessionId);
                
                if ($session->payment_status === 'paid' || $session->payment_status === 'complete') {
                    // Check if this is a new user registration from session
                    $pendingEmail = session('pending_user_email');
                    $pendingName = session('pending_user_name');
                    
                    if ($pendingEmail && $pendingName) {
                        // Check if user already exists (in case they registered during payment)
                        $existingUser = User::where('email', $pendingEmail)->first();
                        
                        if (!$existingUser) {
                            // Create new user account
                            $user = User::create([
                                'name' => $pendingName,
                                'email' => $pendingEmail,
                                'password' => bcrypt(Str::random(12)), // Random password
                                'role' => 'Agency Owner',
                                'email_verified_at' => now(),
                                'subscription_status' => 'active',
                                'subscription_expires_at' => Carbon::now()->addDays(30),
                                'status' => 'active'
                            ]);
                          
                            // Create payment record
                            Payment::create([
                                'user_id' => $user->id,
                                'charge_id' => $session->payment_intent ?? $session->id,
                                'transaction_id' => $session->payment_intent,
                                'amount' => 3000.00,
                                'currency' => 'pkr',
                                'status' => 'completed',
                                'payment_method' => 'stripe',
                                'metadata' => [
                                    'plan_type' => 'Agency Owner Plan',
                                    'subscription_duration' => '30 days',
                                    'user_email' => $user->email,
                                ],
                                'stripe_response' => $session->toArray(),
                            ]);
                            
                            Log::info('New user account created after payment', [
                                'user_id' => $user->id,
                                'email' => $pendingEmail
                            ]);
                        } else {
                            // User exists, just activate subscription
                            $user = $existingUser;
                            $user->update([
                                'subscription_status' => 'active',
                                'subscription_expires_at' => Carbon::now()->addDays(30),
                                'status' => 'active'
                            ]);
                            // Create payment record
                            Payment::create([
                                'user_id' => $user->id,
                                'charge_id' => $session->payment_intent ?? $session->id,
                                'transaction_id' => $session->payment_intent,
                                'amount' => 3000.00,
                                'currency' => 'pkr',
                                'status' => 'completed',
                                'payment_method' => 'stripe',
                                'metadata' => [
                                    'plan_type' => 'Agency Owner Plan',
                                    'subscription_duration' => '30 days',
                                    'user_email' => $user->email,
                                ],
                                'stripe_response' => $session->toArray(),
                            ]);
                        }
                        
                        // Clear pending user session
                        session()->forget(['pending_user_email', 'pending_user_name', 'pending_subscription']);
                        
                        return redirect()->route('filament.admin.auth.login')
                            ->with('success', 'Payment successful! Your account is now active. Please login to continue.');
                    } else {
                        // Existing authenticated user - activate subscription
                        $user = Auth::user();
                        if ($user) {
                            $user->update([
                                'subscription_status' => 'active',
                                'subscription_expires_at' => Carbon::now()->addDays(30)
                            ]);
                            
                            // Create payment record
                            Payment::create([
                                'user_id' => $user->id,
                                'charge_id' => $session->payment_intent ?? $session->id,
                                'transaction_id' => $session->payment_intent,
                                'amount' => 3000.00,
                                'currency' => 'pkr',
                                'status' => 'completed',
                                'payment_method' => 'stripe',
                                'metadata' => [
                                    'plan_type' => 'Agency Owner Plan',
                                    'subscription_duration' => '30 days',
                                    'user_email' => $user->email,
                                ],
                                'stripe_response' => $session->toArray(),
                            ]);
                            
                            return redirect()->route('filament.admin.pages.dashboard')
                                ->with('success', 'Subscription activated successfully!');
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::error('Stripe callback error', [
                    'error' => $e->getMessage(),
                    'session_id' => $sessionId,
                    'payment_status' => $session->payment_status ?? 'unknown'
                ]);
                
                // Debug: Log session data for troubleshooting
                Log::info('Stripe session data', [
                    'session_id' => $sessionId,
                    'session' => $session->toArray()
                ]);
            }
        }
        
        return redirect()->route('subscription.show')
            ->with('error', 'Payment verification failed. Please contact support.');
    }
    
    /**
     * Check current subscription status
     */
    public function checkSubscription()
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        $isActive = $user->subscription_status === 'active' && 
                   $user->subscription_expires_at && 
                   $user->subscription_expires_at > now();
        
        return response()->json([
            'status' => $user->subscription_status,
            'expires_at' => $user->subscription_expires_at,
            'is_active' => $isActive,
            'days_remaining' => $user->subscription_expires_at ? 
                Carbon::now()->diffInDays($user->subscription_expires_at) : 0
        ]);
    }
}
