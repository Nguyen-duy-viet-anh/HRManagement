<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\UserFile;


class ProfileController extends Controller
{
    // 1. Xem hồ sơ
    public function show()
    {
      $user = User::with('files')->find(Auth::id());

        return view('profile.show', compact('user'));
    }

    // 2. Cập nhật hồ sơ
    public function update(Request $request)
    {
        $user = User::find(Auth::id());

        // Validate
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:15',
            'address' => 'nullable|string|max:500',
            'birthday' => 'nullable|date',
            'gender' => 'required|in:male,female,other',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'documents.*' => 'nullable|file|max:5120',
            'password' => 'nullable|min:6|confirmed',
        ]);

        // Cập nhật thông tin cơ bản
        $user->fill($request->only(['name', 'email', 'phone', 'address', 'birthday', 'gender']));

        // LOGIC 1: XỬ LÝ AVATAR (THEO SƠ ĐỒ CỦA BẠN)
        if ($request->hasFile('avatar')) {
            // Xóa ảnh cũ
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }

            $file = $request->file('avatar');
            // Đặt tên file chuẩn: avatar_timestamp.jpg
            $filename = 'avatar_' . time() . '.' . $file->getClientOriginalExtension();

            // CHECK LOGIC FOLDER
            if ($user->company_id) {
                // NHÂN VIÊN CÔNG TY: companies/{id_cty}/users/{id_nv}/
                $folderPath = "companies/{$user->company_id}/users/{$user->id}";
            } else {
                // ADMIN TỔNG: avatars/ (Nằm lẫn lộn ở đây như bạn muốn)
                $folderPath = "avatars";
            }

            // Dùng storeAs để kiểm soát tên file chính xác
            $path = $file->storeAs($folderPath, $filename, 'public');
            $user->avatar = $path;
        }

        // LOGIC 2: XỬ LÝ FILE ĐÍNH KÈM (CCCD, Bằng cấp...)
        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $file) {
                $filename = time() . '_' . $file->getClientOriginalName();
                
                if ($user->company_id) {
                    // Nhân viên: companies/{id_cty}/users/{id_nv}/documents/
                    $docFolder = "companies/{$user->company_id}/users/{$user->id}/documents";
                } else {
                    // Admin: users/{id_admin}/documents/ (Để riêng ra khỏi folder avatars cho đỡ rối)
                    $docFolder = "users/{$user->id}/documents";
                }

                $path = $file->storeAs($docFolder, $filename, 'public');

                UserFile::create([
                    'user_id' => $user->id,
                    'file_path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'type' => 'document'
                ]);
            }
        }

        // Đổi mật khẩu
        if ($request->filled('password')) {
            $user->password = bcrypt($request->password);
        }

        $user->save();

        return back()->with('success', 'Cập nhật hồ sơ thành công!');
    }

    // 3. Xem đồng nghiệp
    public function colleagues()
    {
        $my_company_id = Auth::user()->company_id;
        
        if(!$my_company_id) {
            $colleagues = collect([]); 
        } else {
            $colleagues = User::where('company_id', $my_company_id)
                          ->select('id', 'name', 'email', 'avatar', 'role')
                          ->paginate(10);
        }

        return view('profile.colleagues', compact('colleagues'));
    }

    public function allFiles()
    {
        $user = User::with('files')->find(Auth::id()); 
      
        $files = $user->files()->orderBy('created_at', 'desc')->paginate(20);

        return view('profile.files', compact('files'));
    }
}