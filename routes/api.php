<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::get('/', function (){
    return count(\App\Models\User::all());
});
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/auth/redirect', [AuthController::class, 'redirectToGoogle']);
Route::get('/auth/callback', [AuthController::class, 'handleGoogleCallback']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
Route::post('/logout', [AuthController::class, 'logout']);

// Add these routes for password reset
Route::get('/password/reset/{token}', function ($token) {
    return view('auth.passwords.reset', ['token' => $token]);
})->name('password.reset');
