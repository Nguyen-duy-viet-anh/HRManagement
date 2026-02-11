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



use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\LunchOrderController;
use App\Http\Controllers\Api\LunchPriceController;
use App\Http\Controllers\Api\UserFileController;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::post('/me', [AuthController::class, 'me'])->middleware('auth:sanctum');

Route::middleware(['auth:sanctum', 'role:0,1,2'])->prefix('users')->group(function () {
    Route::post('/list', [UserController::class, 'index']);
    Route::post('/show', [UserController::class, 'show']);
    Route::post('/create', [UserController::class, 'store']);
    Route::post('/update', [UserController::class, 'update']);
    Route::post('/delete', [UserController::class, 'destroy']);
});

Route::middleware(['auth:sanctum', 'role:0'])->prefix('companies')->group(function () {
    Route::post('/list',   [CompanyController::class, 'index']);
    Route::post('/show',   [CompanyController::class, 'show']);
    Route::post('/create', [CompanyController::class, 'store']);
    Route::post('/update', [CompanyController::class, 'update']);
    Route::post('/delete', [CompanyController::class, 'destroy']);
});

Route::middleware(['auth:sanctum', 'role:0,1,2'])->prefix('attendances')->group(function () {
    Route::post('/list',   [AttendanceController::class, 'index']);
    Route::post('/show',   [AttendanceController::class, 'show']);
    Route::post('/create', [AttendanceController::class, 'store']);
    Route::post('/update', [AttendanceController::class, 'update']);
    Route::post('/delete', [AttendanceController::class, 'destroy']);
});

Route::middleware(['auth:sanctum', 'role:0,1,2'])->prefix('lunch-orders')->group(function () {
    Route::post('/list',   [LunchOrderController::class, 'index']);
    Route::post('/show',   [LunchOrderController::class, 'show']);
    Route::post('/create', [LunchOrderController::class, 'store']);
    Route::post('/repay',  [LunchOrderController::class, 'repay']);
    Route::post('/update', [LunchOrderController::class, 'update']);
    Route::post('/delete', [LunchOrderController::class, 'destroy']);
});

Route::middleware(['auth:sanctum', 'role:0'])->prefix('lunch-prices')->group(function () {
    Route::post('/list',   [LunchPriceController::class, 'index']);
    Route::post('/show',   [LunchPriceController::class, 'show']);
    Route::post('/create', [LunchPriceController::class, 'store']);
    Route::post('/update', [LunchPriceController::class, 'update']);
    Route::post('/delete', [LunchPriceController::class, 'destroy']);
});

Route::middleware(['auth:sanctum', 'role:0,1,2'])->prefix('user-files')->group(function () {
    Route::post('/list',   [UserFileController::class, 'index']);
    Route::post('/show',   [UserFileController::class, 'show']);
    Route::post('/upload', [UserFileController::class, 'store']);
    Route::post('/update', [UserFileController::class, 'update']);
    Route::post('/delete', [UserFileController::class, 'destroy']);
});

Route::middleware(['auth:sanctum'])->prefix('notifications')->group(function () {
    Route::post('/list',          [\App\Http\Controllers\Api\NotificationController::class, 'index']);
    Route::post('/read',          [\App\Http\Controllers\Api\NotificationController::class, 'markAsRead']);
    Route::post('/read-all',      [\App\Http\Controllers\Api\NotificationController::class, 'markAllAsRead']);
    Route::post('/delete',        [\App\Http\Controllers\Api\NotificationController::class, 'destroy']);
    Route::post('/unread-count',  [\App\Http\Controllers\Api\NotificationController::class, 'unreadCount']);
});
