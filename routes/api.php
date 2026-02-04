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

// VNPay IPN - Server-to-Server callback (không cần auth)
Route::post('/vnpay/ipn', [\App\Http\Controllers\VnpayController::class, 'handleIPN'])->name('vnpay.ipn');

// ========================================
// ONEPAY PAYMENT GATEWAY ROUTES
// ========================================
// IPN (Instant Payment Notification) - Server-to-Server callback
// QUAN TRỌNG: Endpoint này KHÔNG cần authentication vì OnePay gọi trực tiếp
Route::post('/onepay/ipn', [\App\Http\Controllers\OnepayController::class, 'handleIpn'])->name('onepay.ipn');
