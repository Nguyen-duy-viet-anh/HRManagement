<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
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
            // Điều hướng tất cả về Dashboard
            return redirect()->intended('dashboard');
        }

        return back()->with('error', 'Email hoặc mật khẩu không chính xác.');
    }

    public function logout() {
        Auth::logout();
        return redirect()->route('login');
    }
    
    // HÀM DASHBOARD ĐÃ ĐƯỢC TỐI ƯU HÓA
    public function dashboard() {
    $user = Auth::user();
    $today = date('Y-m-d');
    $currentMonth = date('m');
    $currentYear = date('Y');

    // 1. TẠO KEY CACHE (Để phân biệt Cache của Admin và từng Công ty)
    $cacheKey = "dashboard_stats_role_{$user->role}_comp_{$user->company_id}";

    // 2. SỬ DỤNG CACHE (Lưu trong 10 phút = 600 giây)
    $stats = Cache::remember($cacheKey, 600, function () use ($user, $today) {
        
        // Chỉ giữ lại các thống kê cơ bản, BỎ phần lương
        $data = [
            'total_companies' => 0,
            'total_users' => 0,
            'present_today' => 0,
        ];

        // Thống kê số lượng
        if ($user->role == 0) { // ADMIN TỔNG
            $data['total_companies'] = Company::count();
            $data['total_users'] = User::count();
            
            // Đếm số người đi làm HÔM NAY (quan trọng: phải có where date)
            $data['present_today'] = Attendance::where('date', $today)
                                               ->where('status', 1)
                                               ->count();

        } elseif ($user->role == 1) { // QUẢN LÝ CÔNG TY
            $data['total_companies'] = 1;
            $data['total_users'] = User::where('company_id', $user->company_id)->count();
            
            // Đếm nhân viên công ty mình đi làm hôm nay
            $data['present_today'] = Attendance::where('company_id', $user->company_id)
                                               ->where('date', $today)
                                               ->where('status', 1)
                                               ->count();
        }

        return $data;
    });

    // 3. DỮ LIỆU CÁ NHÂN (Cho người dùng xem trạng thái của chính mình)
    // Phần này không cache vì nó thay đổi liên tục theo từng User
    $todayAttendance = Attendance::where('user_id', $user->id)
        ->where('date', $today)
        ->first();

    $my_work_days = Attendance::where('user_id', $user->id)
        ->whereYear('date', $currentYear)
        ->whereMonth('date', $currentMonth)
        ->where('status', 1)
        ->count();

    // 4. Trả về View
    return view('dashboard', array_merge($stats, [
        'todayAttendance' => $todayAttendance,
        'my_work_days' => $my_work_days
    ]));
}
}