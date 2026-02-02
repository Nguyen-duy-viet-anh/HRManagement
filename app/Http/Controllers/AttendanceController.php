<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Company;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use App\Jobs\StoreAttendanceJob;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    // 1. DANH SÁCH CHẤM CÔNG (LỌC)
    public function index(Request $request)
    {
        $date = $request->input('date', date('Y-m-d'));
        $company_id = $request->input('company_id');
        $search_name = $request->input('search_name'); 
        $status = $request->input('status', 'all'); 

        $user = Auth::user();
        $companies = [];
        
        // Phân quyền lấy danh sách công ty
        if ($user->role == 0) {
            $companies = Company::select('id', 'name')->get();
        } elseif ($user->role == 1) {
            $company_id = $user->company_id;
            $companies = Company::where('id', $user->company_id)->select('id', 'name')->get();
        }

        $users = null;

        if ($company_id) {
            $query = User::where('company_id', $company_id)->where('role', '!=', 0);

            // A. LỌC THEO TÊN
            if ($search_name) {
                $query->where('name', 'LIKE', "%{$search_name}%");
            }

            // B. LỌC THEO TRẠNG THÁI
            if ($status == '1') {
                $query->whereHas('attendances', function ($q) use ($date) {
                    $q->where('date', $date)->where('status', 1);
                });
            } elseif ($status == '0') {
                $query->whereDoesntHave('attendances', function ($q) use ($date) {
                    $q->where('date', $date)->where('status', 1);
                });
            }

            // C. PHÂN TRANG
            $users = $query->orderBy('id', 'asc')->paginate(15); 

            // D. GẮN TRẠNG THÁI HIỂN THỊ
            $userIds = $users->pluck('id')->toArray();
            $attendances = Attendance::whereIn('user_id', $userIds)
                                     ->where('date', $date)
                                     ->get()
                                     ->keyBy('user_id');

            foreach ($users as $u) {
                $att = $attendances->get($u->id);
                $u->is_present = ($att && $att->status == 1);
            }
        }

        // [LƯU Ý] Đã sửa thành 'attendance.index' (số ít) để đồng bộ
        return view('attendances.index', compact('users', 'companies', 'date', 'company_id', 'search_name', 'status'));
    }

    // 2. LƯU CHẤM CÔNG
    public function store(Request $request)
    {
        $user = Auth::user();
        
        if ($user->role == 1) {
            $request->merge(['company_id' => $user->company_id]);
        }

        $request->validate([
            'date' => 'required|date|before_or_equal:today', // Chặn chọn tương lai
            'company_id' => 'required',
        ]);

        $date = $request->date;
        $user_ids = $request->user_ids ?? [];     
        $present_ids = $request->present ?? []; 

        if ($user->role == 1) {
            $validUserIds = User::where('company_id', $user->company_id)
                                ->whereIn('id', $user_ids)
                                ->pluck('id')
                                ->toArray();
            $user_ids = $validUserIds;
        }
        
        StoreAttendanceJob::dispatch($date, $request->company_id, $user_ids, $present_ids);
        
        // Xóa cache Dashboard
        $this->clearDashboardCache($request->company_id);

        return redirect()->route('attendance.index', [
            'company_id' => $request->company_id, 
            'date' => $date,
            'page' => $request->page 
        ])->with('success', 'Đã lưu chấm công thành công.');
    }

    // 3. NHÂN VIÊN TỰ CHECK-IN
    public function selfCheckIn()
    {
        $user = Auth::user(); 
        $date = date('Y-m-d');
        
        // Tìm bản ghi chấm công bất kỳ của user trong ngày
        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', $date)
            ->first();

        // Nếu đã có bản ghi và đã chấm công "có mặt" thì báo lại
        if ($attendance && $attendance->status == 1) {
            return back()->with('info', 'Bạn đã chấm công hôm nay rồi.');
        }

        // Dùng updateOrCreate:
        // - Nếu chưa có record -> tạo mới với status = 1
        // - Nếu có record (chắc chắn là status = 0) -> update thành status = 1
        Attendance::updateOrCreate(
            [
                'user_id' => $user->id,
                'date'    => $date,
            ],
            [
                'company_id' => $user->company_id,
                'status'     => 1,
                'check_in_time' => Carbon::now()->toTimeString(),
            ]
        );

        $this->clearDashboardCache($user->company_id);

        return back()->with('success', 'Đã chấm công thành công.');
    }

    // 4. LỊCH SỬ CÁ NHÂN (NV Tự xem)
    public function history()
    {
        $targetUser = Auth::user(); 
        
        $attendances = Attendance::where('user_id', $targetUser->id)
                        ->orderBy('date', 'desc')
                        ->paginate(20);

        return view('attendances.history', compact('attendances', 'targetUser'));
    }

    // 5. LỊCH SỬ CỦA NHÂN VIÊN (Admin xem)
    public function userAttendance($id)
    {
        $currentUser = Auth::user();
        $targetUser = User::findOrFail($id); 

        if ($currentUser->role == 1 && $targetUser->company_id != $currentUser->company_id) {
            abort(403, 'Không có quyền xem nhân viên công ty khác.');
        }

        $attendances = Attendance::where('user_id', $targetUser->id)
                        ->orderBy('date', 'desc')
                        ->paginate(20);

        return view('attendances.history', compact('attendances', 'targetUser'));
    }

    // --- Private Helper ---
    private function clearDashboardCache($companyId)
    {
        Cache::forget("dashboard_stats_role_0_comp_"); 
        if ($companyId) {
            Cache::forget("dashboard_stats_role_1_comp_{$companyId}");
        }
    }
}