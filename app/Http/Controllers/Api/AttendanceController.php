<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Illuminate\Http\Request;

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
}
