<!DOCTYPE html>
<html lang="en" class="bg-black transition-colors duration-300">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agency Owner Subscription - Stitching Fund</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-black text-white">  
   
    
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <!-- Header -->
            <div class="text-center">
                <h1 class="text-3xl font-bold text-white mb-2">
                    <i class="fas fa-crown text-purple-400 mr-2"></i>
                    Agency Subscription
                </h1>
                <p class="text-gray-300">Activate your subscription to access all features</p>
            </div>

            <!-- Subscription Card -->
            <div class="bg-gray-900 border border-purple-800 rounded-lg shadow-lg overflow-hidden">
                <div class="bg-gradient-to-r from-purple-600 to-purple-700 p-6 text-white">
                    <h2 class="text-2xl font-bold">{{ $planName }}</h2>
                    <div class="mt-4">
                        <span class="text-4xl font-bold">$10</span>
                        <span class="text-lg">/{{ $planDuration }}</span>
                    </div>
                </div>
                
                <div class="p-6 bg-gray-800">
                    <!-- Features -->
                    <div class="space-y-3 mb-6">
                        <div class="flex items-center text-purple-400">
                            <i class="fas fa-check-circle mr-3"></i>
                            <span class="text-gray-200">Full access to investment management</span>
                        </div>
                        <div class="flex items-center text-purple-400">
                            <i class="fas fa-check-circle mr-3"></i>
                            <span class="text-gray-200">Unlimited investor management</span>
                        </div>
                        <div class="flex items-center text-purple-400">
                            <i class="fas fa-check-circle mr-3"></i>
                            <span class="text-gray-200">Wallet and transaction management</span>
                        </div>
                        <div class="flex items-center text-purple-400">
                            <i class="fas fa-check-circle mr-3"></i>
                            <span class="text-gray-200">Advanced reporting and analytics</span>
                        </div>
                    </div>

                    <!-- Current Status -->
                    @if(isset($user) && ($user->subscription_status === 'active' && $user->subscription_expires_at))
                    <div class="bg-green-900 border border-green-700 rounded-lg p-4 mb-6">
                        <div class="flex items-center text-green-400">
                            <i class="fas fa-check-circle mr-2"></i>
                            <span class="font-medium">Active Subscription</span>
                        </div>
                        <p class="text-sm text-green-300 mt-1">
                            Expires: {{ $user->subscription_expires_at->format('M d, Y') }}
                        </p>
                    </div>
                    @else
                    <div class="bg-red-900 border border-red-700 rounded-lg p-4 mb-6">
                        <div class="flex items-center text-red-400">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            <span class="font-medium">No Active Subscription</span>
                        </div>
                        <p class="text-sm text-red-300 mt-1">
                            Subscribe to access all platform features
                        </p>
                    </div>
                    <!-- Payment Form -->
                    <form id="paymentForm" method="POST" action="{{ route('subscription.pay') }}" class="space-y-4">
                        @csrf
                        <input type="hidden" name="payment_method" value="stripe">
                        
                        @if(!isset($user))
                        <!-- User Information Fields for New Users -->
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Full Name</label>
                            <input type="text" name="name" required 
                                class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                placeholder="Enter your full name">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Email Address</label>
                            <input type="email" name="email" required 
                                class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                placeholder="Enter your email address">
                        </div>
                        @endif
                        
                        <button type="submit" class="w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 px-4 rounded-lg transition duration-200 flex items-center justify-center">
                            <i class="fas fa-credit-card mr-2"></i>
                            Subscribe Now - PKR 3,000
                        </button>
                    </form>
                </div>
                @endif
          
            </div>
        </div>

            </div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-gray-900 border border-purple-700 rounded-lg p-6 flex items-center space-x-3">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600"></div>
            <span class="text-white">Processing payment...</span>
        </div>
    </div>

    <!-- Success/Error Messages -->
    @if(session('success'))
    <div class="fixed top-4 right-4 bg-green-900 border border-green-700 text-green-300 px-4 py-3 rounded-lg z-50">
        <div class="flex items-center">
            <i class="fas fa-check-circle mr-2"></i>
            {{ session('success') }}
        </div>
    </div>
    @endif

    @if(session('error'))
    <div class="fixed top-4 right-4 bg-red-900 border border-red-700 text-red-300 px-4 py-3 rounded-lg z-50">
        <div class="flex items-center">
            <i class="fas fa-exclamation-circle mr-2"></i>
            {{ session('error') }}
        </div>
    </div>
    @endif

    @if(session('warning'))
    <div class="fixed top-4 right-4 bg-yellow-900 border border-yellow-700 text-yellow-300 px-4 py-3 rounded-lg z-50">
        <div class="flex items-center">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            {{ session('warning') }}
        </div>
    </div>
    @endif

    <!-- JavaScript for Payment Form -->
    <script>
        document.getElementById("paymentForm").addEventListener("submit", async function(e) {
            e.preventDefault();
            
            const loadingOverlay = document.getElementById('loadingOverlay');
            const formData = new FormData(this);
            
            // Show loading state
            loadingOverlay.classList.remove('hidden');
            
            try {
                const response = await fetch("{{ route('subscription.pay') }}", {
                    method: "POST",
                    body: formData,
                    headers: {
                        "X-CSRF-TOKEN": formData.get("_token"),
                        "Accept": "application/json"
                    }
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Redirect to Stripe
                    window.location.href = result.redirect_url;
                } else {
                    // Show error message
                    alert(result.message || 'Payment failed. Please try again.');
                    loadingOverlay.classList.add('hidden');
                }
            } catch (error) {
                console.error('Payment error:', error);
                alert('Network error. Please try again.');
                loadingOverlay.classList.add('hidden');
            }
        });
    </script>
   
</body>
</html>
