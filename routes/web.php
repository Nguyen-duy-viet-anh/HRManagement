<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\SalaryController;
use App\Http\Controllers\ProfileController; 

// ====================================================
// 1. KHU VỰC CÔNG CỘNG 
// ====================================================
Route::get('/', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ====================================================
// 2. KHU VỰC ĐÃ ĐĂNG NHẬP 
// ====================================================
Route::middleware('auth')->group(function () {
    
    // Dashboard chung
    Route::get('/dashboard', [AuthController::class, 'dashboard'])->name('dashboard');

    // ----------------------------------------------------
    // NHÓM 1: CÁC CHỨC NĂNG CÔNG TY NHẠY CẢM (Chỉ Admin - Role 0)
    // Quyền: Xem danh sách, Tạo mới, Xóa công ty
    // ----------------------------------------------------
    Route::middleware(['role:0'])->group(function() {
        Route::get('/companies', [CompanyController::class, 'index'])->name('companies.index');
        Route::get('/companies/create', [CompanyController::class, 'create'])->name('companies.create');
        Route::post('/companies', [CompanyController::class, 'store'])->name('companies.store');
        Route::delete('/companies/{company}', [CompanyController::class, 'destroy'])->name('companies.destroy');
    });

    // ----------------------------------------------------
    // NHÓM 2: QUẢN LÝ & ADMIN (Role 0 & 1)
    // Quyền: Quản lý nhân sự, chấm công, lương VÀ SỬA CÔNG TY
    // ----------------------------------------------------
    Route::middleware(['role:0,1'])->group(function() {
        
        // MỚI: Cho phép Role 1 vào sửa công ty (Controller sẽ chặn nếu sửa sai công ty)
        Route::get('/companies/{company}/edit', [CompanyController::class, 'edit'])->name('companies.edit');
        Route::put('/companies/{company}', [CompanyController::class, 'update'])->name('companies.update');

        // Quản lý nhân viên
        Route::resource('users', UserController::class);
        Route::post('/users/import', [UserController::class, 'importExcel'])->name('users.import');

        // Chấm công
        Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
        Route::post('/attendance', [AttendanceController::class, 'store'])->name('attendance.store');
        
        // Lương
        Route::get('/salary', [SalaryController::class, 'index'])->name('salary.index');
        Route::get('/salary/export', [SalaryController::class, 'export'])->name('salary.export');
    });

    // ----------------------------------------------------
    // NHÓM 3: NHÂN VIÊN (Role 2)
    // ----------------------------------------------------
    Route::middleware(['role:2'])->group(function() {
        Route::get('/my-profile', [ProfileController::class, 'show'])->name('profile.show');
        Route::post('/my-profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::get('/colleagues', [ProfileController::class, 'colleagues'])->name('colleagues.index');
        Route::post('/self-check-in', [AttendanceController::class, 'selfCheckIn'])->name('attendance.self');
    });

});

// ROUTE CỨU HỘ
Route::get('/reset-all', function() {
    Artisan::call('route:clear');
    Artisan::call('config:clear');
    Artisan::call('cache:clear');
    Session::flush();
    return '<h1>ĐÃ RESET THÀNH CÔNG! <a href="/">Đăng nhập lại</a></h1>';
});