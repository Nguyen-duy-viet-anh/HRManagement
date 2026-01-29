<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\SalaryController;
use App\Http\Controllers\ProfileController; 
use App\Http\Controllers\NotificationController;

Route::get('/', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    
    Route::get('/dashboard', [AuthController::class, 'dashboard'])->name('dashboard');

    Route::delete('/user-files/{id}', [UserController::class, 'deleteFile'])->name('user_files.destroy');

    // NHÓM 1: CÁC CHỨC NĂNG CÔNG TY (Chỉ Admin - Role 0)
    Route::middleware(['role:0'])->group(function() {
        Route::get('/companies', [CompanyController::class, 'index'])->name('companies.index');
        Route::get('/companies/create', [CompanyController::class, 'create'])->name('companies.create');
        Route::post('/companies', [CompanyController::class, 'store'])->name('companies.store');
        Route::delete('/companies/{company}', [CompanyController::class, 'destroy'])->name('companies.destroy');
        Route::get('/admin/notify', [NotificationController::class, 'create'])->name('notify.create');
        Route::post('/admin/notify', [NotificationController::class, 'send'])->name('notify.send');
        // Route::post('/send', [NotificationController::class, 'send'])->name('guithongbao');
    });

    // NHÓM 2: QUẢN LÝ & ADMIN (Role 0 & 1)
    Route::middleware(['role:0,1'])->group(function() {
        
        // Quản lý công ty (Sửa)
        Route::get('/companies/{company}/edit', [CompanyController::class, 'edit'])->name('companies.edit');
        Route::put('/companies/{company}', [CompanyController::class, 'update'])->name('companies.update');

        // Quản lý nhân viên
        Route::resource('users', UserController::class);
        Route::post('/users/import', [UserController::class, 'importExcel'])->name('users.import');
        
        // Xem Album ảnh của nhân viên (Dành cho Admin/Quản lý soi)
        Route::get('/users/{id}/files', [UserController::class, 'userFiles'])->name('users.files');

        // Chấm công
        Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
        Route::post('/attendance', [AttendanceController::class, 'store'])->name('attendance.store');
        Route::get('/users/{id}/attendance', [App\Http\Controllers\AttendanceController::class, 'userAttendance'])->name('users.attendance');

        
        // Lương
        Route::get('/salary', [SalaryController::class, 'index'])->name('salary.index');
        Route::get('/salary/export', [SalaryController::class, 'export'])->name('salary.export');
    });

    
    // NHÓM 3: NHÂN VIÊN (Role 2)

    Route::middleware(['role:2'])->group(function() {
        Route::get('/my-profile', [ProfileController::class, 'show'])->name('profile.show');
        Route::post('/my-profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::get('/my-profile/files', [ProfileController::class, 'allFiles'])->name('profile.files');
        Route::get('/colleagues', [ProfileController::class, 'colleagues'])->name('colleagues.index');
        Route::post('/self-check-in', [AttendanceController::class, 'selfCheckIn'])->name('attendance.self');
        Route::get('/my-attendance-history', [AttendanceController::class, 'history'])->name('attendance.history');
        Route::get('/mark-as-read', [NotificationController::class, 'markAsRead'])->name('notify.read');
        Route::get('/notify/read', [NotificationController::class, 'markAsRead'])->name('notify.read');
    });

});