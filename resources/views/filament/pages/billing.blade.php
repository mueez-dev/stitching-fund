<x-filament-panels::page>
    {{-- @include('components.subscription-popups') --}}
    
    <div class="space-y-6">
        <!-- Subscription Status Section -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Subscription Status</h2>
            <p class="text-sm text-gray-600 mb-6">Manage your subscription and billing information</p>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <div>{!! $this->getSubscriptionStatusBadge() !!}</div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Expires On</label>
                    <div class="text-sm text-gray-900">
                        {{ $this->user->subscription_expires_at ? $this->user->subscription_expires_at->format('M d, Y') : 'Never' }}
                    </div>
                </div>
            </div>
            
            <div class="mt-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Details</label>
                <div class="text-sm text-gray-900 bg-gray-50 p-4 rounded-md">
                    {{ $this->getSubscriptionDetails() }}
                </div>
            </div>
        </div>
        
        <!-- Actions Section -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Actions</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @if(in_array($this->getSubscriptionState(), ['expiring', 'expired_grace', 'locked']))
                <div>
                    <button 
                        wire:click="renewSubscription"
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                        </svg>
                        Renew Subscription
                    </button>
                </div>
                @endif
                
                <div>
                    <button 
                        wire:click="viewHistory"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Payment History
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
