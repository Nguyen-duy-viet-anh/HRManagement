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

// ========================================
// VNPAY PAYMENT GATEWAY - IPN
// ========================================
// IPN (Instant Payment Notification) - Server-to-Server callback
// QUAN TRỌNG: 
// - Endpoint này KHÔNG cần authentication vì VNPay gọi trực tiếp
// - Đây là nguồn CHÍNH để cập nhật đơn hàng
// - Return URL chỉ dùng để hiển thị kết quả cho user
Route::match(['get', 'post'], '/vnpay/ipn', [\App\Http\Controllers\VnpayController::class, 'handleIPN'])->name('vnpay.ipn');

// ========================================
// ONEPAY PAYMENT GATEWAY - IPN
// ========================================
// IPN (Instant Payment Notification) - Server-to-Server callback
// QUAN TRỌNG:
// - Endpoint này KHÔNG cần authentication vì OnePay gọi trực tiếp
// - Đây là nguồn CHÍNH để cập nhật đơn hàng
// - Return URL chỉ dùng để hiển thị kết quả cho user
Route::match(['get', 'post'], '/onepay/ipn', [\App\Http\Controllers\OnepayController::class, 'handleIpn'])->name('onepay.ipn');


// test api user
use App\Http\Controllers\Api\UserApiController;

Route::get('/users', [UserApiController::class, 'index']);
Route::get('/users/{id}', [UserApiController::class, 'show']);
Route::post('/users', [UserApiController::class, 'store']);
Route::put('/users/{id}', [UserApiController::class, 'update']);
Route::delete('/users/{id}', [UserApiController::class, 'destroy']);

