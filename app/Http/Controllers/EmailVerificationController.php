<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    public function index(Request $request)
    {
        $email = session('verification_email');
        
        if (!$email) {
            return redirect()->route('filament.admin.auth.login')
                ->with('error', 'No email found for verification. Please register again.');
        }
        
        return view('email-verification', compact('email'));
    }
}
