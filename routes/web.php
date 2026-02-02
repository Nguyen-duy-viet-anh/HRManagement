<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\SalaryController;
use App\Http\Controllers\ProfileController; 
use App\Http\Controllers\NotificationController; // Đã import gọn lại
use App\Http\Controllers\LunchController;

// --- ROUTE KHÔNG CẦN ĐĂNG NHẬP ---
Route::get('/', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// --- ROUTE CẦN ĐĂNG NHẬP (AUTH) ---
Route::middleware('auth')->group(function () {
    
    Route::get('/dashboard', [AuthController::class, 'dashboard'])->name('dashboard');
    Route::delete('/user-files/{id}', [UserController::class, 'deleteFile'])->name('user_files.destroy');
    Route::get('/notification/read/{id}', [NotificationController::class, 'read'])->name('notification.read');
    Route::get('/notification/read-all', [NotificationController::class, 'markAsRead'])->name('notifications.markAllRead');


    Route::middleware(['role:0'])->group(function() {
        // Quản lý công ty
        Route::get('/companies', [CompanyController::class, 'index'])->name('companies.index');
        Route::get('/companies/create', [CompanyController::class, 'create'])->name('companies.create');
        Route::post('/companies', [CompanyController::class, 'store'])->name('companies.store');
        Route::delete('/companies/{company}', [CompanyController::class, 'destroy'])->name('companies.destroy');

        Route::get('/admin/notifications/create', [NotificationController::class, 'create'])->name('notifications.create');
        Route::post('/admin/notifications/send', [NotificationController::class, 'send'])->name('notifications.send');

        // Cấu hình phiếu ăn
        Route::get('/lunch/config', [LunchController::class, 'config'])->name('lunch.config');
        Route::post('/lunch/config', [LunchController::class, 'storePrice'])->name('lunch.store_price');
        Route::delete('/lunch/config/{id}', [LunchController::class, 'deletePrice'])->name('lunch.delete_price');
    });
    // NHÓM 2: QUẢN LÝ & ADMIN (Role 0 & 1)
    Route::middleware(['role:0,1'])->group(function() {
        
        // Quản lý công ty (Sửa)
        Route::get('/companies/{company}/edit', [CompanyController::class, 'edit'])->name('companies.edit');
        Route::put('/companies/{company}', [CompanyController::class, 'update'])->name('companies.update');

        // Quản lý nhân viên
        Route::resource('users', UserController::class);
        Route::post('/users/import', [UserController::class, 'importExcel'])->name('users.import');
        Route::get('/users/{id}/files', [UserController::class, 'userFiles'])->name('users.files');

        // Chấm công
        Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
        Route::post('/attendance', [AttendanceController::class, 'store'])->name('attendance.store');
        Route::get('/users/{id}/attendance', [AttendanceController::class, 'userAttendance'])->name('users.attendance');
        
        // Lương
        Route::get('/salary', [SalaryController::class, 'index'])->name('salary.index');
        Route::get('/salary/export', [SalaryController::class, 'export'])->name('salary.export');

        // Thống kê đặt cơm trưa
        Route::get('/lunch/stats', [LunchController::class, 'stats'])->name('lunch.stats');
    });

    // ==========================================================
    // NHÓM 3: NHÂN VIÊN (Role 2)
    // ==========================================================
    Route::middleware(['role:2'])->group(function() {
        Route::get('/my-profile', [ProfileController::class, 'show'])->name('profile.show');
        Route::post('/my-profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::get('/my-profile/files', [ProfileController::class, 'allFiles'])->name('profile.files');
        Route::get('/colleagues', [ProfileController::class, 'colleagues'])->name('colleagues.index');
        
        // Chấm công cá nhân
        Route::post('/self-check-in', [AttendanceController::class, 'selfCheckIn'])->name('attendance.self');
        Route::get('/my-attendance-history', [AttendanceController::class, 'history'])->name('attendance.history');
    });

    // --- ROUTE CHUNG CHO CƠM TRƯA (Admin cũng có thể vào mua/xem) ---
    Route::get('/lunch', [LunchController::class, 'index'])->name('lunch.index');
    Route::post('/lunch/order', [LunchController::class, 'order'])->name('lunch.order');
    Route::get('/lunch/return', [LunchController::class, 'vnpayReturn'])->name('lunch.return');
    Route::get('/lunch/repay/{id}', [LunchController::class, 'repay'])->name('lunch.repay');
});