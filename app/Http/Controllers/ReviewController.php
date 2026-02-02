<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ReviewController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate(Review::validationRules());
            
            $user = Auth::user();
            
            // Check if user already submitted a review
            $existingReview = Review::where('user_id', $user->id)->first();
            if ($existingReview) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have already submitted a review.'
                ], 422);
            }
            
            $review = Review::create([
                'user_id' => $user->id,
                'review_text' => $validated['review_text'],
                'rating' => $validated['rating'],
                'status' => 'pending', // Requires admin approval
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Review submitted successfully! It will be visible after approval.',
                'review' => $review->load('user')
            ]);
            
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while submitting your review.'
            ], 500);
        }
    }
    
    public function getApprovedReviews(): JsonResponse
    {
        $reviews = Review::where('status', 'approved')
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();
            
        return response()->json([
            'success' => true,
            'reviews' => $reviews
        ]);
    }
    
    public function checkUserReview(): JsonResponse
    {
        $user = Auth::user();
        $review = Review::where('user_id', $user->id)->first();
        
        return response()->json([
            'has_review' => !is_null($review),
            'review' => $review
        ]);
    }
}
