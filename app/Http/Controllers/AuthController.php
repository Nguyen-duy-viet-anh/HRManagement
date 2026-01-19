<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Company;
use App\Models\Attendance;

class AuthController extends Controller
{
    public function showLogin() {
        if (Auth::check()) return redirect()->route('dashboard');
        return view('login');
    }

    public function login(Request $request) {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            if ($user->role == 2) {
                return redirect()->intended('dashboard');
            }

            // Role 0 và Role 1 vẫn hiện thông báo như cũ
            return redirect()->intended('dashboard');
        }

        return back()->with('error', 'Email hoặc mật khẩu không chính xác.');
    }

    public function logout() {
        Auth::logout();
        return redirect()->route('login');
    }
    
    public function dashboard() {
        $user = Auth::user();
        $today = date('Y-m-d');
        $currentMonth = date('m');
        $currentYear = date('Y');

        // 1. Khởi tạo dữ liệu mặc định để tránh lỗi Undefined variable
        $total_companies = 0;
        $total_users = 0;
        $present_today = 0;
        $total_estimated_salary = 0;
        $todayAttendance = null;
        $attendances = collect(); // Tạo collection rỗng tránh lỗi vòng lặp foreach

        // 2. Thống kê theo Vai trò
        if ($user->role == 0) { // ADMIN TỔNG
            $total_companies = Company::count();
            $total_users = User::count();
            $present_today = Attendance::where('date', $today)->where('status', 1)->count();
            
            $attendances = Attendance::whereYear('date', $currentYear)
                ->whereMonth('date', $currentMonth)
                ->where('status', 1)
                ->with(['user.company']) // Nhớ thêm quan hệ này trong Model Attendance
                ->get();

        } elseif ($user->role == 1) { // QUẢN LÝ CÔNG TY
            $total_companies = 1;
            $total_users = User::where('company_id', $user->company_id)->count();
            $present_today = Attendance::where('company_id', $user->company_id)
                ->where('date', $today)
                ->where('status', 1)
                ->count();

            $attendances = Attendance::where('company_id', $user->company_id)
                ->whereYear('date', $currentYear)
                ->whereMonth('date', $currentMonth)
                ->where('status', 1)
                ->with(['user.company'])
                ->get();
        }

        // 3. Tính toán lương dự kiến (Chỉ chạy nếu có dữ liệu chấm công)
        if ($attendances->isNotEmpty()) {
            foreach($attendances as $att) {
                if($att->user && $att->user->company && $att->user->company->standard_working_days > 0) {
                    // $DailySalary = \frac{BaseSalary}{StandardWorkingDays}$
                    $daily_salary = $att->user->base_salary / $att->user->company->standard_working_days;
                    $total_estimated_salary += $daily_salary;
                }
            }
        }

        // 4. Riêng cho Nhân viên (Role 2)
        if ($user->role == 2) {
            $todayAttendance = Attendance::where('user_id', $user->id)
                ->where('date', $today)
                ->first();
        }

        // 5. Số ngày công tháng này
        $my_work_days = Attendance::where('user_id', $user->id)
            ->whereYear('date', $currentYear)
            ->whereMonth('date', $currentMonth)
            ->where('status', 1)
            ->count();

        return view('dashboard', compact(
            'total_companies', 'total_users', 'present_today', 
            'total_estimated_salary', 'todayAttendance', 'my_work_days'
        ));
    }
}