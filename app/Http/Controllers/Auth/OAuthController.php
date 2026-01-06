<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\LeaveQuota;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;
use Exception;

class OAuthController extends Controller
{
    /**
     * Redirect to Google OAuth
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    /**
     * Handle Google OAuth callback
     */
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
            
            // Find or create user
            $user = User::where('email', $googleUser->email)->first();

            if ($user) {
                // Update existing user with OAuth info
                $user->update([
                    'provider' => 'google',
                    'provider_id' => $googleUser->id,
                    'avatar' => $googleUser->avatar,
                    'email_verified_at' => $user->email_verified_at ?? now(),
                ]);
            } else {
                // Create new user
                $user = User::create([
                    'name' => $googleUser->name,
                    'email' => $googleUser->email,
                    'provider' => 'google',
                    'provider_id' => $googleUser->id,
                    'avatar' => $googleUser->avatar,
                    'role' => 'employee', // default role
                    'email_verified_at' => now(),
                ]);

                // Create leave quota for new user
                LeaveQuota::create([
                    'user_id' => $user->id,
                    'year' => now()->year,
                    'total' => 12,
                    'used' => 0,
                    'remaining' => 12,
                ]);
            }

            // Create token
            $token = $user->createToken('auth-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'user' => $user,
                    'token' => $token,
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'OAuth authentication failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}