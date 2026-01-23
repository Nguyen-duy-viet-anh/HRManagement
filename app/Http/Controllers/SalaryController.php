<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Company;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB   ; // Cần thêm dòng này để dùng DB::raw
use Illuminate\Support\Facades\Auth;
class SalaryController extends Controller
{
    // File: app/Http/Controllers/SalaryController.php

public function index(Request $request)
{
    $user = Auth::user();
    $month = $request->input('month', date('Y-m'));
    
    // --- LOGIC PHÂN QUYỀN ---
    if ($user->role == 1) {
        $company_id = $user->company_id; // Ép buộc
    } else {
        $company_id = $request->input('company_id'); // Admin chọn
    }
    // -----------------------

    // Logic lấy danh sách công ty cho Dropdown
    $companies = [];
    if ($user->role == 0) {
        $companies = Company::select('id', 'name')->get();
    } elseif ($user->role == 1) {
        $companies = Company::where('id', $user->company_id)->select('id', 'name')->get();
    }

    $users = null;

    if ($company_id) {
        $company = Company::find($company_id);
        
        // Bảo mật phụ: Nếu tìm không thấy công ty hoặc cố tình nhập ID sai
        if (!$company || ($user->role == 1 && $company->id != $user->company_id)) {
            abort(403, 'Bạn không có quyền xem bảng lương này.');
        }

        $standard_days = $company->standard_working_days ?: 26;

        // Code tối ưu (đã có từ trước)
        $users = User::where('users.company_id', $company_id) // Lưu ý: phải có 'users.'
                     ->select('id', 'name', 'email', 'base_salary')
                     ->paginate(15); 

        $userIds = $users->pluck('id')->toArray();

        $attendanceCounts = Attendance::whereIn('user_id', $userIds)
            ->where('date', 'like', "$month%")
            ->where('status', 1)
            ->selectRaw('user_id, COUNT(*) as work_days')
            ->groupBy('user_id')
            ->pluck('work_days', 'user_id');

        foreach ($users as $u) {
            $work_days = $attendanceCounts->get($u->id, 0);
            $u->work_days = $work_days;
            $salary_per_day = ($standard_days > 0) ? ($u->base_salary / $standard_days) : 0;
            $u->total_salary = round($salary_per_day * $work_days);
        }
    }

    return view('salaries.index', compact('users', 'companies', 'month', 'company_id'));
}

public function export(Request $request)
{
    $user = Auth::user();
    $month = $request->input('month', date('Y-m'));
    
    // --- LOGIC PHÂN QUYỀN EXPORT ---
    if ($user->role == 1) {
        $company_id = $user->company_id;
    } else {
        $company_id = $request->input('company_id');
    }
    // ------------------------------

    if (!$company_id) {
        return back()->with('error', 'Vui lòng chọn công ty.');
    }

    // ... (Phần còn lại của logic export giữ nguyên, 
    // code này sẽ tự động chạy với $company_id đã được kiểm duyệt ở trên)
    
    // Code cũ của bạn ở đây...
}
}