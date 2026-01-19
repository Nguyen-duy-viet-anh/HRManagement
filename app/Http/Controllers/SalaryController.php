<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Company;
use App\Models\Attendance;
use Illuminate\Http\Request;

class SalaryController extends Controller
{
    public function index(Request $request)
    {
        // 1. Lấy tháng và công ty cần xem lương (Mặc định là tháng hiện tại)
        $month = $request->input('month', date('Y-m'));
        $company_id = $request->input('company_id');

        $users = [];
        $companies = Company::all();

        if ($company_id) {
            // Lấy công ty để biết "Ngày công chuẩn" (ví dụ 26 ngày/tháng)
            $company = Company::find($company_id);
            $standard_days = $company->standard_working_days; 

            // Lấy danh sách nhân viên công ty đó
            $users = User::where('company_id', $company_id)->get();

            // Vòng lặp tính lương cho từng người
            foreach ($users as $user) {
                // Đếm số ngày đi làm trong tháng này (status = 1)
                // whereLike '2023-10%'
                $work_days = Attendance::where('user_id', $user->id)
                                ->where('date', 'like', "$month%") 
                                ->where('status', 1)
                                ->count();
                
                // Tính lương: (Lương CB / Ngày chuẩn) * Ngày làm
                // Làm tròn số tiền để đỡ bị số lẻ
                $salary_per_day = ($standard_days > 0) ? ($user->base_salary / $standard_days) : 0;
                $total_salary = $salary_per_day * $work_days;

                // Gán tạm dữ liệu vào biến user để mang sang View hiển thị
                $user->work_days = $work_days;
                $user->total_salary = round($total_salary);
            }
        }

        return view('salaries.index', compact('users', 'companies', 'month', 'company_id'));
    }
    // Hàm xuất Excel
    public function export(Request $request)
    {
        // 1. Copy y hệt logic lọc dữ liệu của hàm index
        $month = $request->input('month', date('Y-m'));
        $company_id = $request->input('company_id');
        $users = [];

        if ($company_id) {
            $company = Company::find($company_id);
            $standard_days = $company->standard_working_days; 
            $users = User::where('company_id', $company_id)->get();

            foreach ($users as $user) {
                $work_days = Attendance::where('user_id', $user->id)
                                ->where('date', 'like', "$month%") 
                                ->where('status', 1)->count();
                
                $salary_per_day = ($standard_days > 0) ? ($user->base_salary / $standard_days) : 0;
                $user->work_days = $work_days;
                $user->total_salary = round($salary_per_day * $work_days);
            }
        }

        // 2. Thay vì trả về View bình thường, ta trả về file download
        // Đặt tên file là: Bang_luong_Thang_X.xls
        $fileName = 'Bang_luong_' . $month . '.xls';

        return response(view('salaries.export', compact('users', 'month')), 200, [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => "attachment; filename=\"$fileName\"",
        ]);
    }
}