<!-- Review Popup Modal -->
<div id="review-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-md w-full p-6 relative">
        <!-- Close Button -->
        <button onclick="closeReviewModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
        
        <!-- Modal Header -->
        <div class="mb-6">
            <h3 class="text-xl font-bold text-gray-900 mb-2">Share Your Experience</h3>
            <p class="text-gray-600">Help us improve by sharing your thoughts about ZARYQ</p>
        </div>
        
        <!-- Review Form -->
        <form id="review-form" onsubmit="submitReview(event)">
            <!-- Rating Stars -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Rating</label>
                <div class="flex space-x-2" id="rating-stars">
                    <button type="button" onclick="setRating(1)" class="star-btn text-2xl text-gray-300 hover:text-yellow-400 focus:outline-none" data-rating="1">★</button>
                    <button type="button" onclick="setRating(2)" class="star-btn text-2xl text-gray-300 hover:text-yellow-400 focus:outline-none" data-rating="2">★</button>
                    <button type="button" onclick="setRating(3)" class="star-btn text-2xl text-gray-300 hover:text-yellow-400 focus:outline-none" data-rating="3">★</button>
                    <button type="button" onclick="setRating(4)" class="star-btn text-2xl text-gray-300 hover:text-yellow-400 focus:outline-none" data-rating="4">★</button>
                    <button type="button" onclick="setRating(5)" class="star-btn text-2xl text-gray-300 hover:text-yellow-400 focus:outline-none" data-rating="5">★</button>
                </div>
                <input type="hidden" id="rating-input" name="rating" required>
            </div>
            
            <!-- Review Text -->
            <div class="mb-4">
                <label for="review-text" class="block text-sm font-medium text-gray-700 mb-2">Your Review</label>
                <textarea 
                    id="review-text" 
                    name="review_text" 
                    rows="4" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                    placeholder="Tell us about your experience with ZARYQ..."
                    required
                    minlength="10"
                    maxlength="1000"
                ></textarea>
                <div class="text-xs text-gray-500 mt-1">Minimum 10 characters, maximum 1000 characters</div>
            </div>
            
            <!-- Submit Button -->
            <div class="flex justify-end space-x-3">
                <button 
                    type="button" 
                    onclick="closeReviewModal()" 
                    class="px-4 py-2 text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500"
                >
                    Cancel
                </button>
                <button 
                    type="submit" 
                    class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 disabled:opacity-50"
                    id="submit-btn"
                >
                    Submit Review
                </button>
            </div>
        </form>
        
        <!-- Loading State -->
        <div id="loading-state" class="hidden text-center py-4">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600"></div>
            <p class="mt-2 text-gray-600">Submitting...</p>
        </div>
        
        <!-- Success State -->
        <div id="success-state" class="hidden text-center py-4">
            <div class="text-green-600 mb-2">
                <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <p class="text-gray-700 font-medium">Review submitted successfully!</p>
            <p class="text-gray-600 text-sm mt-1">It will be visible after admin approval.</p>
        </div>
        
        <!-- Error State -->
        <div id="error-state" class="hidden text-center py-4">
            <div class="text-red-600 mb-2">
                <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </div>
            <p class="text-gray-700 font-medium">Error submitting review</p>
            <p class="text-gray-600 text-sm mt-1" id="error-message">Please try again.</p>
            <button 
                onclick="resetReviewForm()" 
                class="mt-3 px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500"
            >
                Try Again
            </button>
        </div>
    </div>
</div>

<style>
.star-btn.active {
    color: #fbbf24;
}
</style>
