<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\SalaryController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\LunchOrderController;
use App\Http\Controllers\Api\LunchPriceController;
use App\Http\Controllers\Api\UserFileController;

// --- ROUTE KHÔNG CẦN ĐĂNG NHẬP ---
Route::post('/login', [AuthController::class, 'login']);

// --- ROUTE CẦN ĐĂNG NHẬP (AUTH) ---
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/me',     [AuthController::class, 'me']);
    Route::post('/user-files/delete', [UserFileController::class, 'destroy']);
    Route::post('/notifications/read',     [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);


    // NHÓM 1: CHỈ ADMIN (Role 0)
    Route::middleware(['role:0'])->group(function () {
        // Quản lý công ty
        Route::post('/companies/list',   [CompanyController::class, 'index']);
        Route::post('/companies/show',   [CompanyController::class, 'show']);
        Route::post('/companies/create', [CompanyController::class, 'store']);
        Route::post('/companies/delete', [CompanyController::class, 'destroy']);

        // Thông báo
        Route::post('/notifications/create', [NotificationController::class, 'create']);

        // Cấu hình phiếu ăn
        Route::post('/lunch-prices/list',   [LunchPriceController::class, 'index']);
        Route::post('/lunch-prices/show',   [LunchPriceController::class, 'show']);
        Route::post('/lunch-prices/create', [LunchPriceController::class, 'store']);
        Route::post('/lunch-prices/update', [LunchPriceController::class, 'update']);
        Route::post('/lunch-prices/delete', [LunchPriceController::class, 'destroy']);
    });

    // NHÓM 2: QUẢN LÝ & ADMIN (Role 0 & 1)
    Route::middleware(['role:0,1'])->group(function () {

        // Quản lý công ty (Sửa)
        Route::post('/companies/update', [CompanyController::class, 'update']);

        // Quản lý nhân viên
        Route::post('/users/list',   [UserController::class, 'index']);
        Route::post('/users/show',   [UserController::class, 'show']);
        Route::post('/users/create', [UserController::class, 'store']);
        Route::post('/users/update', [UserController::class, 'update']);
        Route::post('/users/delete', [UserController::class, 'destroy']);
        Route::post('/users/files',  [UserController::class, 'userFiles']);

        // Quản lý file nhân viên
        Route::post('/user-files/list',   [UserFileController::class, 'index']);
        Route::post('/user-files/show',   [UserFileController::class, 'show']);
        Route::post('/user-files/upload', [UserFileController::class, 'store']);
        Route::post('/user-files/update', [UserFileController::class, 'update']);

        // Chấm công
        Route::post('/attendances/list',            [AttendanceController::class, 'index']);
        Route::post('/attendances/show',            [AttendanceController::class, 'show']);
        Route::post('/attendances/create',          [AttendanceController::class, 'store']);
        Route::post('/attendances/update',          [AttendanceController::class, 'update']);
        Route::post('/attendances/delete',          [AttendanceController::class, 'destroy']);
        Route::post('/attendances/user-attendance', [AttendanceController::class, 'userAttendance']);

        // Lương
        Route::post('/salary/list',   [SalaryController::class, 'index']);
        Route::post('/salary/export', [SalaryController::class, 'export']);

        // Thống kê đặt cơm trưa
        Route::post('/lunch-orders/stats',     [LunchOrderController::class, 'stats']);
        Route::post('/lunch-orders/user-logs', [LunchOrderController::class, 'userLogs']);
        Route::post('/lunch-orders/all-logs',  [LunchOrderController::class, 'allLogs']);
        Route::post('/lunch-orders/update-order', [LunchOrderController::class, 'update']);
    });

    // ==========================================================
    // NHÓM 3: NHÂN VIÊN (Role 2)
    // ==========================================================
    Route::middleware(['role:2'])->group(function () {
        Route::post('/profile/show',       [ProfileController::class, 'show']);
        Route::post('/profile/update',     [ProfileController::class, 'update']);
        Route::post('/profile/files',      [ProfileController::class, 'allFiles']);
        Route::post('/profile/colleagues', [ProfileController::class, 'colleagues']);

        // Chấm công cá nhân
        Route::post('/attendances/self-check-in', [AttendanceController::class, 'selfCheckIn']);
        Route::post('/attendances/history',       [AttendanceController::class, 'history']);
    });

    // --- ROUTE CHUNG CHO CƠM TRƯA (Tất cả user đã đăng nhập) ---
    Route::post('/lunch-orders/list',   [LunchOrderController::class, 'index']);
    Route::post('/lunch-orders/show',   [LunchOrderController::class, 'show']);
    Route::post('/lunch-orders/create', [LunchOrderController::class, 'store']);
    Route::post('/lunch-orders/repay',  [LunchOrderController::class, 'repay']);
    Route::post('/lunch-orders/delete', [LunchOrderController::class, 'destroy']);

    // Thông báo chung
    Route::post('/notifications/list',         [NotificationController::class, 'index']);
    Route::post('/notifications/delete',       [NotificationController::class, 'destroy']);
    Route::post('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
});
