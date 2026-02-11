<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Company;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SalaryController extends Controller
{
    // POST /api/salary/list
    public function index(Request $request)
    {
        $user = $request->user();
        $month = $request->input('month', date('Y-m'));

        // Phân quyền
        if ($user->role == 1) {
            $companyId = $user->company_id;
        } else {
            $companyId = $request->input('company_id');
        }

        if (!$companyId) {
            return apiError('Vui lòng chọn công ty', 400);
        }

        $company = Company::find($companyId);

        if (!$company) {
            return apiError('Không tìm thấy công ty', 404);
        }

        // Bảo mật: Quản lý chỉ xem công ty mình
        if ($user->role == 1 && $company->id != $user->company_id) {
            return apiError('Không có quyền xem bảng lương công ty khác', 403);
        }

        $standardDays = $company->standard_working_days ?: 26;

        $users = User::where('company_id', $companyId)
            ->select('id', 'name', 'email', 'base_salary')
            ->paginate(20);

        $userIds = $users->pluck('id')->toArray();

        $attendanceCounts = Attendance::whereIn('user_id', $userIds)
            ->where('date', 'like', "$month%")
            ->where('status', 1)
            ->selectRaw('user_id, COUNT(*) as work_days')
            ->groupBy('user_id')
            ->pluck('work_days', 'user_id');

        // Tính lương cho từng nhân viên
        $usersData = $users->getCollection()->map(function ($u) use ($attendanceCounts, $standardDays) {
            $workDays = $attendanceCounts->get($u->id, 0);
            $salaryPerDay = ($standardDays > 0) ? ($u->base_salary / $standardDays) : 0;
            $totalSalary = round($salaryPerDay * $workDays);

            return [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'base_salary' => $u->base_salary,
                'work_days' => $workDays,
                'standard_days' => $standardDays,
                'salary_per_day' => round($salaryPerDay),
                'total_salary' => $totalSalary,
            ];
        });

        return apiSuccess([
            'company' => $company,
            'month' => $month,
            'standard_days' => $standardDays,
            'employees' => $usersData,
            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ], 'Bảng lương nhân viên');
    }

    // POST /api/salary/export
    public function export(Request $request)
    {
        $user = $request->user();
        $month = $request->input('month', date('Y-m'));

        if ($user->role == 1) {
            $companyId = $user->company_id;
        } else {
            $companyId = $request->input('company_id');
        }

        if (!$companyId) {
            return apiError('Vui lòng chọn công ty', 400);
        }

        $company = Company::find($companyId);

        if (!$company) {
            return apiError('Không tìm thấy công ty', 404);
        }

        if ($user->role == 1 && $company->id != $user->company_id) {
            return apiError('Không có quyền xuất bảng lương công ty khác', 403);
        }

        $standardDays = $company->standard_working_days ?: 26;

        // Lấy tất cả (không phân trang) để export
        $users = User::where('company_id', $companyId)
            ->select('id', 'name', 'email', 'base_salary')
            ->get();

        $userIds = $users->pluck('id')->toArray();

        $attendanceCounts = Attendance::whereIn('user_id', $userIds)
            ->where('date', 'like', "$month%")
            ->where('status', 1)
            ->selectRaw('user_id, COUNT(*) as work_days')
            ->groupBy('user_id')
            ->pluck('work_days', 'user_id');

        $employeesData = $users->map(function ($u) use ($attendanceCounts, $standardDays) {
            $workDays = $attendanceCounts->get($u->id, 0);
            $salaryPerDay = ($standardDays > 0) ? ($u->base_salary / $standardDays) : 0;
            $totalSalary = round($salaryPerDay * $workDays);

            return [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'base_salary' => $u->base_salary,
                'work_days' => $workDays,
                'standard_days' => $standardDays,
                'salary_per_day' => round($salaryPerDay),
                'total_salary' => $totalSalary,
            ];
        });

        return apiSuccess([
            'company' => $company,
            'month' => $month,
            'employees' => $employeesData,
        ], 'Dữ liệu xuất bảng lương');
    }
}
