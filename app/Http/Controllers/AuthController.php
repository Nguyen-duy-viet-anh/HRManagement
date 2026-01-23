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
        // Ví dụ: dashboard_stats_role_0_comp_null (Admin)
        // Ví dụ: dashboard_stats_role_1_comp_5 (Manager công ty 5)
        $cacheKey = "dashboard_stats_role_{$user->role}_comp_{$user->company_id}";

        // 2. SỬ DỤNG CACHE CHO CÁC THỐNG KÊ NẶNG (Lưu trong 30 phút = 1800 giây)
        $stats = Cache::remember($cacheKey, 1800, function () use ($user, $today, $currentMonth, $currentYear) {
            
            // Các giá trị mặc định
            $data = [
                'total_companies' => 0,
                'total_users' => 0,
                'present_today' => 0,
                'total_estimated_salary' => 0,
            ];

            // A. Thống kê số lượng cơ bản
            if ($user->role == 0) { // ADMIN TỔNG
                $data['total_companies'] = Company::count();
                $data['total_users'] = User::count();
                $data['present_today'] = Attendance::where('status', 1)->count();
            } elseif ($user->role == 1) { // QUẢN LÝ CÔNG TY
                $data['total_companies'] = 1;
                $data['total_users'] = User::where('company_id', $user->company_id)->count();
                $data['present_today'] = Attendance::where('company_id', $user->company_id)
                    
                    ->where('status', 1)
                    ->count();
            }


            // B. Tính toán lương dự kiến
            if (0&&$user->role != 2) {
                $salaryQuery = Attendance::where('attendances.status', 1);
                dd($salaryQuery->get());
                // Lọc theo công ty nếu là Manager
                if ($user->role == 1) {
                    $salaryQuery->where('users.company_id', $user->company_id);
                }

                // Dùng selectRaw để database tự tính, tránh kéo dữ liệu về PHP
                $result = $salaryQuery->selectRaw('SUM(users.base_salary / NULLIF(companies.standard_working_days, 0)) as total_salary')
                                      ->first();

                $data['total_estimated_salary'] = $result ? (float) $result->total_salary : 0;
            }

            return $data;
        });

        // 3. DỮ LIỆU CÁ NHÂN (KHÔNG CACHE vì thay đổi liên tục theo từng User)
        $todayAttendance = null;
        if ($user->role == 2) {
            $todayAttendance = Attendance::where('user_id', $user->id)
                ->where('date', $today)
                ->first();
        }

        $my_work_days = Attendance::where('user_id', $user->id)
            ->whereYear('date', $currentYear)
            ->whereMonth('date', $currentMonth)
            ->where('status', 1)
            ->count();

        // 4. Trả về View (Gộp dữ liệu từ Cache và dữ liệu cá nhân)
        return view('dashboard', array_merge($stats, [
            'todayAttendance' => $todayAttendance,
            'my_work_days' => $my_work_days
        ]));
    }
}