<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Login API - không cần auth
Route::post('/login', function (Request $request) {
    $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    $user = \App\Models\User::where('email', $request->email)->first();

    if (!$user || !\Illuminate\Support\Facades\Hash::check($request->password, $user->password)) {
        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    $token = $user->createToken('API Token')->plainTextToken;

    return response()->json([
        'user' => $user,
        'token' => $token,
        'token_type' => 'Bearer'
    ]);
});

// VNPay IPN - Server-to-Server callback (không cần auth)
Route::post('/vnpay/ipn', [\App\Http\Controllers\VnpayController::class, 'handleIPN'])->name('vnpay.ipn');

// ========================================
// ONEPAY PAYMENT GATEWAY ROUTES
// ========================================
// IPN (Instant Payment Notification) - Server-to-Server callback
// QUAN TRỌNG: Endpoint này KHÔNG cần authentication vì OnePay gọi trực tiếp
Route::post('/onepay/ipn', [\App\Http\Controllers\OnepayController::class, 'handleIpn'])->name('onepay.ipn');



use App\Http\Controllers\Api\UserController;

// test api user
Route::get('/users', [\App\Http\Controllers\Api\UserController::class, 'index']);
Route::get('/users/{id}', [\App\Http\Controllers\Api\UserController::class, 'show']);
Route::post('/users', [\App\Http\Controllers\Api\UserController::class, 'store']);
Route::put('/users/{id}', [\App\Http\Controllers\Api\UserController::class, 'update']);
Route::delete('/users/{id}', [\App\Http\Controllers\Api\UserController::class, 'destroy']);
