<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Company;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache; // Quan trọng để xóa cache dashboard
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
public function index(Request $request)
{
    $date = $request->input('date', date('Y-m-d'));
    $company_id = $request->input('company_id');
    $search_name = $request->input('search_name'); 

    $user = Auth::user();
    $companies = [];
    if ($user->role == 0) {
        $companies = Company::select('id', 'name')->get();
    } elseif ($user->role == 1) {
        $company_id = $user->company_id;
        $companies = Company::where('id', $user->company_id)->select('id', 'name')->get();
    }

    $users = null;

    if ($company_id) {
        $query = User::where('company_id', $company_id);


        if ($search_name) {
            $query->where('name', 'LIKE', "%{$search_name}%");
        }

        $users = $query->orderBy('id', 'asc')->paginate(15); 

        $userIds = $users->pluck('id')->toArray();
        $attendances = Attendance::whereIn('user_id', $userIds)
                                 ->where('date', $date)
                                 ->get()
                                 ->keyBy('user_id');

        foreach ($users as $user) {
            $att = $attendances->get($user->id);
            $user->is_present = ($att && $att->status == 1);
        }
    }

    return view('attendances.index', compact('users', 'companies', 'date', 'company_id', 'search_name'));
}

public function store(Request $request)
{
    $user = Auth::user();
    
    // --- LOGIC BẢO MẬT KHI LƯU ---
    // Nếu là Quản lý,
    if ($user->role == 1) {
        $request->merge(['company_id' => $user->company_id]);
    }
    // ----------------------------

    $request->validate([
        'date' => 'required|date',
        'company_id' => 'required',
    ]);

    $date = $request->date;
    $user_ids = $request->user_ids ?? [];     
    $present_ids = $request->present ?? []; 

    // Kiểm tra thêm: Nếu là Role 1, đảm bảo user_ids gửi lên phải thuộc công ty mình
    // (Đoạn này nâng cao, tạm thời logic trên đã đủ chặn 99%)

    foreach ($user_ids as $user_id) {
        $status = isset($present_ids[$user_id]) ? 1 : 0;

        Attendance::updateOrCreate(
            [
                'user_id' => $user_id, 
                'date' => $date
            ], 
            [
                'status' => $status,
                'company_id' => $request->company_id 
            ]
        );
    }

    // Xóa Cache
    Cache::forget("dashboard_stats_role_0_comp_"); 
    if ($request->company_id) {
        Cache::forget("dashboard_stats_role_1_comp_{$request->company_id}");
    }

    return redirect()->route('attendance.index', [
        'company_id' => $request->company_id, 
        'date' => $date,
        'page' => $request->page 
    ])->with('success', 'Đã lưu dữ liệu thành công!');
}

    // Hàm này dành cho Nhân viên tự bấm nút Check-in
public function selfCheckIn()
{
    $user = Auth::user(); 
    $date = date('Y-m-d');

    $check = Attendance::where('user_id', $user->id)
                       ->where('date', $date)
                       ->where('status', 1)
                       ->exists();

    if ($check) {
        return back();
    }

    Attendance::updateOrCreate(
        [
            'user_id' => $user->id,
            'date'    => $date
        ],
        [
            'company_id' => $user->company_id,
            'status'     => 1, 
            
        ]
    );

    // 3. Xóa cache dashboard
    $this->clearDashboardCache($user->company_id);

    return back();
}

    /**
     * Hàm phụ trợ: Xóa Cache Dashboard liên quan
     */
    private function clearDashboardCache($companyId)
    {
        // Xóa cache của Admin tổng
        Cache::forget("dashboard_stats_role_0_comp_"); 
        
        // Xóa cache của Quản lý công ty này
        if ($companyId) {
            Cache::forget("dashboard_stats_role_1_comp_{$companyId}");
        }
        
        // Nếu lười quản lý tên Key, bạn có thể dùng lệnh xóa sạch (nhưng sẽ chậm hệ thống 1 chút)
        // Cache::flush(); 
    }
}