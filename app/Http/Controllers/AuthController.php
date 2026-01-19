<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // Gọi thư viện xác thực của Laravel
use App\Models\User;
use App\Models\Company;
use App\Models\Attendance;

class AuthController extends Controller
{
    // Hàm 1: Chỉ đơn giản là hiện ra cái form đăng nhập
    public function showLogin() {
        // Nếu đăng nhập rồi thì đá sang trang dashboard luôn, ko cần đăng nhập lại
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('login');
    }

    // Hàm 2: Xử lý khi bấm nút "Đăng nhập"
    public function login(Request $request) {
        // 1. Lấy email và password người dùng nhập
        $credentials = $request->only('email', 'password');

        // 2. Auth::attempt sẽ tự động mã hóa pass và so sánh với DB
        if (Auth::attempt($credentials)) {
            // Nếu ĐÚNG: Tạo session đăng nhập và chuyển hướng
            return redirect()->route('dashboard')->with('success', 'Đăng nhập thành công!');
        }

        // Nếu SAI: Quay lại trang cũ và báo lỗi
        return back()->with('error', 'Email hoặc mật khẩu sai rồi!');
    }

    // Hàm 3: Đăng xuất
    public function logout() {
        Auth::logout(); // Xóa session
        return redirect()->route('login'); // Quay về trang login
    }
    
    public function dashboard() {
        $user = \Illuminate\Support\Facades\Auth::user();

        // --- KHỞI TẠO BIẾN MẶC ĐỊNH ---
        $total_companies = 0;
        $total_users = 0;
        $present_today = 0;
        $total_estimated_salary = 0;
        $todayAttendance = null;
        $my_work_days = 0; 

        // 1. TÍNH SỐ NGÀY CÔNG CỦA CHÍNH MÌNH (Ai cũng cần)
        if ($user->company_id) {
            $my_work_days = \App\Models\Attendance::where('user_id', $user->id)
                                ->whereYear('date', date('Y'))
                                ->whereMonth('date', date('m'))
                                ->where('status', 1)
                                ->count();
        }

        // 2. LOGIC CHO QUẢN LÝ (Role 0 & 1)
        if ($user->role == 0 || $user->role == 1) {
            
            // --- TRƯỜNG HỢP ADMIN TỔNG (Role 0) ---
            // Xem tất cả, không giới hạn
            if ($user->role == 0) {
                $total_companies = \App\Models\Company::count();
                $total_users = \App\Models\User::count();
                
                // Đếm tất cả người đi làm
                $present_today = \App\Models\Attendance::where('date', date('Y-m-d'))
                                        ->where('status', 1)->count();
                
                // Lấy tất cả chấm công để tính lương
                $attendances = \App\Models\Attendance::whereYear('date', date('Y'))
                                    ->whereMonth('date', date('m'))
                                    ->where('status', 1)
                                    ->with('user.company')->get();
            }

            // --- TRƯỜNG HỢP QUẢN LÝ CÔNG TY (Role 1) ---
            // Chỉ xem dữ liệu của công ty mình
            if ($user->role == 1) {
                // Tổng công ty luôn là 1 (chính là công ty của họ)
                $total_companies = 1; 

                // Chỉ đếm nhân viên CÙNG công ty
                $total_users = \App\Models\User::where('company_id', $user->company_id)->count();

                // Chỉ đếm người đi làm thuộc công ty này
                // Dùng whereHas để lọc qua bảng user
                $present_today = \App\Models\Attendance::whereHas('user', function($q) use ($user) {
                                            $q->where('company_id', $user->company_id);
                                        })
                                        ->where('date', date('Y-m-d'))
                                        ->where('status', 1)
                                        ->count();

                // Chỉ lấy chấm công của nhân viên công ty này để tính lương
                $attendances = \App\Models\Attendance::whereHas('user', function($q) use ($user) {
                                            $q->where('company_id', $user->company_id);
                                        })
                                        ->whereYear('date', date('Y'))
                                        ->whereMonth('date', date('m'))
                                        ->where('status', 1)
                                        ->with('user.company')->get();
            }

            // TÍNH LƯƠNG (Dùng chung logic cho cả 2 role sau khi đã lọc xong biến $attendances)
            foreach($attendances as $att) {
                if($att->user && $att->user->company && $att->user->company->standard_working_days > 0) {
                    $daily_salary = $att->user->base_salary / $att->user->company->standard_working_days;
                    $total_estimated_salary += $daily_salary;
                }
            }
        }

        // 3. LOGIC CHO NHÂN VIÊN (Role 2)
        if ($user->role == 2) {
            $todayAttendance = \App\Models\Attendance::where('user_id', $user->id)
                                ->where('date', date('Y-m-d'))
                                ->first();
        }

        return view('dashboard', compact(
            'total_companies', 
            'total_users', 
            'present_today', 
            'total_estimated_salary', 
            'todayAttendance',
            'my_work_days'
        ));
    }
    
}