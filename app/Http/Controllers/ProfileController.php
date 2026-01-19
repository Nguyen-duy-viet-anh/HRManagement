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
        $user = Auth::user();
        
        // Chỉ cho phép sửa Tên, Email, Mật khẩu
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'password' => 'nullable|min:6'
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            // Không có dòng base_salary hay role ở đây => An toàn
        ];

        if ($request->password) {
            $data['password'] = Hash::make($request->password);
        }

        User::where('id', $user->id)->update($data);

        return back()->with('success', 'Cập nhật hồ sơ thành công!');
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