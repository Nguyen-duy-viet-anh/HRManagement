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

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

// // Login API - không cần auth
// Route::post('/login', function (Request $request) {
//     $request->validate([
//         'email' => 'required|email',
//         'password' => 'required',
//     ]);

//     $user = \App\Models\User::where('email', $request->email)->first();

//     if (!$user || !\Illuminate\Support\Facades\Hash::check($request->password, $user->password)) {
//         return response()->json(['message' => 'Invalid credentials'], 401);
//     }

//     $token = $user->createToken('API Token')->plainTextToken;

//     return response()->json([
//         'user' => $user,
//         'token' => $token,
//         'token_type' => 'Bearer'
//     ]);
// });

// // VNPay IPN - Server-to-Server callback (không cần auth)
// Route::post('/vnpay/ipn', [\App\Http\Controllers\VnpayController::class, 'handleIPN'])->name('vnpay.ipn');

// // ========================================
// // ONEPAY PAYMENT GATEWAY ROUTES
// // ========================================
// // IPN (Instant Payment Notification) - Server-to-Server callback
// // QUAN TRỌNG: Endpoint này KHÔNG cần authentication vì OnePay gọi trực tiếp
// Route::post('/onepay/ipn', [\App\Http\Controllers\OnepayController::class, 'handleIpn'])->name('onepay.ipn');



// test api user
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\AuthController;

Route::prefix('users')->group(function () {
    Route::post('/list', [UserController::class, 'index']);
    Route::post('/show', [UserController::class, 'show']);
    Route::post('/create', [UserController::class, 'store']);
    Route::post('/update', [UserController::class, 'update']);
    Route::post('/delete', [UserController::class, 'destroy']);
});
Route::prefix('companies')->group(function () {
    Route::post('/list',   [CompanyController::class, 'index']);  
    Route::post('/show', [CompanyController::class, 'show']); 
    Route::post('/create', [CompanyController::class, 'store']); 
    Route::post('/update', [CompanyController::class, 'update']);  
    Route::post('/delete', [CompanyController::class, 'destroy']);
});

Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::post('/me', [AuthController::class, 'me'])->middleware('auth:sanctum');
