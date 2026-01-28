<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class ProfileController extends Controller
{
    // 1. Xem hồ sơ bản thân
    public function show()
    {
        $user = Auth::user(); // Lấy người đang đăng nhập
        return view('profile.show', compact('user'));
    }

    // 2. Cập nhật thông tin (Chặn sửa lương, role)
    public function update(Request $request)
{
    $user = \App\Models\User::find(\Illuminate\Support\Facades\Auth::id());

    // 1. Validate dữ liệu đầu vào
    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email,' . $user->id,
        'phone' => 'nullable|string|max:15',
        'address' => 'nullable|string|max:500',
        'birthday' => 'nullable|date',
        'gender' => 'required|in:male,female,other',
        'avatar' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        'password' => 'nullable|min:6|confirmed',
    ], [
        'email.unique' => 'Email này đã có người sử dụng.',
        'password.confirmed' => 'Mật khẩu xác nhận không khớp.'
    ]);

    // 2. Cập nhật các cột thông tin cá nhân
    $user->name = $request->name;
    $user->email = $request->email;
    $user->phone = $request->phone;
    $user->address = $request->address;
    $user->birthday = $request->birthday;
    $user->gender = $request->gender;

    // 3. Xử lý Upload ảnh (nếu có)
    if ($request->hasFile('avatar')) {
        // Xóa ảnh cũ
        if ($user->avatar && \Illuminate\Support\Facades\Storage::disk('public')->exists($user->avatar)) {
             \Illuminate\Support\Facades\Storage::disk('public')->delete($user->avatar);
        }

        // Kiểm tra logic lưu trữ giống UserController
        if ($user->company_id) {
            $folderPath = "companies/{$user->company_id}/users/{$user->id}";
        } else {
            $folderPath = "avatars";
        }

        $file = $request->file('avatar');
        $filename = 'avatar_' . time() . '.' . $file->getClientOriginalExtension();
        
        // Dùng storeAs để đặt tên file và đường dẫn cụ thể
        $path = $file->storeAs($folderPath, $filename, 'public');
        $user->avatar = $path;
    }

    // 4. Xử lý đổi mật khẩu (chỉ đổi nếu người dùng nhập)
    if ($request->filled('password')) {
        $user->password = bcrypt($request->password);
    }

    $user->save();

    return back()->with('success', 'Hồ sơ của bạn đã được cập nhật thành công!');
}
    // 3. Xem danh sách đồng nghiệp (Ẩn lương)
    public function colleagues()
    {
        $my_company_id = Auth::user()->company_id;
        // Chỉ lấy các trường cơ bản, KHÔNG lấy base_salary
        $colleagues = User::where('company_id', $my_company_id)
                          ->select('id', 'name', 'email', 'avatar') 
                          ->paginate(10);

        return view('profile.colleagues', compact('colleagues'));
    }
}