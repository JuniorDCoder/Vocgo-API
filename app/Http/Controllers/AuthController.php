<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterUserRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use App\Models\User;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    // Register a new user
    public function register(RegisterUserRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('authToken')->accessToken;

        return response()->json(['token' => $token, 'user' => $user], 201);
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            $token = $user->createToken('authToken')->accessToken;
            return response()->json(['token' => $token, 'user' => auth()->user()], 200);
        }

        return response()->json(['error' => 'These Credentials do not match our records'], 401);
    }

    // Redirect to Google for authentication
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    // Handle Google callback
    public function handleGoogleCallback()
    {
        $googleUser = Socialite::driver('google')->stateless()->user();
        $user = User::firstOrCreate(
            ['email' => $googleUser->getEmail()],
            ['name' => $googleUser->getName(), 'password' => Hash::make(Str::random(24))]
        );

        Auth::login($user);
        $token = $user->createToken('authToken')->accessToken;

        return response()->json(['token' => $token], 200);
    }

    // Forgot password
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['message' => 'Email does not exist'], 404);
        }

        $status = Password::sendResetLink($request->only('email'));

        return $status === Password::RESET_LINK_SENT
            ? response()->json(['message' => __($status)], 200)
            : response()->json(['email' => __($status)], 400);
    }

    // Reset password
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? response()->json(['message' => __($status)], 200)
            : response()->json(['email' => [__($status)]], 400);
    }

    public function logout()
    {
        $user = auth()->user();
        if ($user) {
            $user->token()->revoke();
            return response()->json(['message' => 'Logged out successfully'], 200);
        }

        return response()->json(['error' => 'Unable to logout'], 500);
    }
}
