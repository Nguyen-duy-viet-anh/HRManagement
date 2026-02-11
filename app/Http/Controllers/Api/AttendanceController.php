<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    // POST /api/attendances/list
    public function index(Request $request)
    {
        $query = Attendance::with(['user', 'company'])->orderByDesc('date');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->filled('date')) {
            $query->where('date', $request->date);
        }

        if ($request->filled('from_date') && $request->filled('to_date')) {
            $query->whereBetween('date', [$request->from_date, $request->to_date]);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return apiSuccess(
            $query->paginate(20),
            'Danh sách chấm công'
        );
    }

    // POST /api/attendances/show
    public function show(Request $request)
    {
        $request->validate([
            'id' => 'required|uuid'
        ]);

        $attendance = Attendance::with(['user', 'company'])->find($request->id);

        if (!$attendance) {
            return apiError('Không tìm thấy bản ghi chấm công', 404);
        }

        return apiSuccess($attendance, 'Chi tiết chấm công');
    }

    // POST /api/attendances/create
    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id'       => 'required|uuid|exists:users,id',
            'company_id'    => 'required|string|exists:companies,id',
            'date'          => 'required|date',
            'check_in_time' => 'nullable|date_format:H:i:s',
            'status'        => 'required|in:0,1'
        ]);

        return apiSuccess(
            Attendance::create($data),
            'Tạo bản ghi chấm công thành công',
            201
        );
    }

    // POST /api/attendances/update
    public function update(Request $request)
    {
        $data = $request->validate([
            'id'            => 'required|uuid',
            'user_id'       => 'sometimes|required|uuid|exists:users,id',
            'company_id'    => 'sometimes|required|string|exists:companies,id',
            'date'          => 'sometimes|required|date',
            'check_in_time' => 'nullable|date_format:H:i:s',
            'status'        => 'sometimes|required|in:0,1'
        ]);

        $attendance = Attendance::find($data['id']);

        if (!$attendance) {
            return apiError('Không tìm thấy bản ghi chấm công', 404);
        }

        $attendance->update($data);

        return apiSuccess($attendance, 'Cập nhật chấm công thành công');
    }

    // POST /api/attendances/delete
    public function destroy(Request $request)
    {
        $request->validate([
            'id' => 'required|uuid'
        ]);

        $attendance = Attendance::find($request->id);

        if (!$attendance) {
            return apiError('Không tìm thấy bản ghi chấm công', 404);
        }

        $attendance->delete();

        return apiSuccess(null, 'Xóa bản ghi chấm công thành công');
    }

    // POST /api/attendances/self-check-in
    public function selfCheckIn(Request $request)
    {
        $user = $request->user();
        $date = date('Y-m-d');

        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', $date)
            ->first();

        if ($attendance && $attendance->status == 1) {
            return apiError('Bạn đã chấm công hôm nay rồi', 400);
        }

        $attendance = Attendance::updateOrCreate(
            [
                'user_id' => $user->id,
                'date'    => $date,
            ],
            [
                'company_id'    => $user->company_id,
                'status'        => 1,
                'check_in_time' => Carbon::now()->toTimeString(),
            ]
        );

        // Xóa cache dashboard
        Cache::forget("dashboard_stats_role_0_comp_");
        if ($user->company_id) {
            Cache::forget("dashboard_stats_role_1_comp_{$user->company_id}");
        }

        return apiSuccess($attendance, 'Chấm công thành công');
    }

    // POST /api/attendances/history
    public function history(Request $request)
    {
        $user = $request->user();

        $query = Attendance::where('user_id', $user->id)
            ->orderBy('date', 'desc');

        if ($request->filled('month') && $request->filled('year')) {
            $query->whereYear('date', $request->year)
                  ->whereMonth('date', $request->month);
        }

        return apiSuccess($query->paginate(20), 'Lịch sử chấm công cá nhân');
    }

    // POST /api/attendances/user-attendance
    public function userAttendance(Request $request)
    {
        $request->validate([
            'user_id' => 'required|uuid|exists:users,id'
        ]);

        $currentUser = $request->user();
        $targetUser = User::find($request->user_id);

        // Quản lý chỉ xem nhân viên công ty mình
        if ($currentUser->role == 1 && $targetUser->company_id != $currentUser->company_id) {
            return apiError('Không có quyền xem nhân viên công ty khác', 403);
        }

        $query = Attendance::where('user_id', $targetUser->id)
            ->orderBy('date', 'desc');

        if ($request->filled('month') && $request->filled('year')) {
            $query->whereYear('date', $request->year)
                  ->whereMonth('date', $request->month);
        }

        return apiSuccess([
            'user' => $targetUser,
            'attendances' => $query->paginate(20)
        ], 'Lịch sử chấm công của nhân viên');
    }
}
