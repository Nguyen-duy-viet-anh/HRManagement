<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Company;
use App\Models\Attendance;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        // 1. Lấy dữ liệu từ bộ lọc
        $date = $request->input('date', date('Y-m-d')); // Mặc định hôm nay
        $company_id = $request->input('company_id');

        $companies = Company::all();
        $users = null;

        if ($company_id) {
            // 2. Lấy nhân viên (Phân trang 10 người để ko bị treo máy)
            $users = User::where('company_id', $company_id)->paginate(10);

            // 3. Kiểm tra xem từng người hôm nay đã chấm công chưa
            foreach ($users as $user) {
                $attendance = Attendance::where('user_id', $user->id)
                                ->where('date', $date)
                                ->first();
                
                // Gán cờ 'is_present' (true/false) để View biết mà tích sẵn
                $user->is_present = ($attendance && $attendance->status == 1);
            }
        }

        return view('attendances.index', compact('users', 'companies', 'date', 'company_id'));
    }

    public function store(Request $request)
    {
        // Validate dữ liệu
        $request->validate([
            'date' => 'required|date',
            'user_ids' => 'required|array', // Danh sách ID nhân viên đang hiển thị
        ]);

        $date = $request->date;
        $user_ids = $request->user_ids;     // Mảng chứa tất cả ID nhân viên ở trang hiện tại
        $present_ids = $request->present ?? []; // Mảng chứa ID những người ĐƯỢC TÍCH (Đi làm)

        foreach ($user_ids as $user_id) {
            // Kiểm tra: ID này có nằm trong danh sách được tích không?
            // Có -> status 1 (Đi làm). Không -> status 0 (Nghỉ)
            $status = isset($present_ids[$user_id]) ? 1 : 0;

            Attendance::updateOrCreate(
                ['user_id' => $user_id, 'date' => $date], // Điều kiện tìm
                ['status' => $status]                      // Dữ liệu cập nhật
            );
        }

        return redirect()->route('attendance.index', [
            'company_id' => $request->company_id, 
            'date' => $date,
            'page' => $request->page // Giữ nguyên trang hiện tại
        ])->with('success', 'Đã lưu chấm công thành công!');
    }
    // Hàm này dành cho Nhân viên tự bấm nút
    public function selfCheckIn()
{
    $user = \Illuminate\Support\Facades\Auth::user(); // Lấy toàn bộ thông tin User
    $date = date('Y-m-d');

    // 1. Kiểm tra xem hôm nay đã chấm chưa?
    $check = Attendance::where('user_id', $user->id)
                       ->where('date', $date)
                       ->first();

    if ($check) {
        return back()->with('error', 'Hôm nay bạn đã chấm công rồi!');
    }

    // 2. Thêm company_id vào lệnh tạo mới
    Attendance::create([
        'user_id'    => $user->id,
        'company_id' => $user->company_id, // BẮT BUỘC phải có dòng này
        'date'       => $date,
        'status'     => 1 
    ]);
}
}