<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\WalletController;
use App\Filament\Register\Pages\RegisterPage;
use App\Http\Controllers\Demo\DemoRegisterController;



Route::get('/', function () {
    return view('welcome');
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


