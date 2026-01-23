<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 

class UserController extends Controller
{
    // 1. XEM DANH SÁCH
    public function index(Request $request)
    {
        $user = Auth::user();
        $search = $request->input('search');
        $company_id = $request->input('company_id');
        
        $query = User::with('company');

        // Phân quyền
        if ($user->role == 1) {
            $query->where('company_id', $user->company_id);
        } else {
            if ($company_id) {
                $query->where('company_id', $company_id);
            }
        }

        // Tìm kiếm
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        $users = $query->orderBy('id', 'desc')->Paginate(100);
        
        $companies = [];
        if ($user->role == 0) {
            $companies = Company::select('id', 'name')->get();
        }

        return view('users.index', compact('users', 'search', 'companies', 'company_id'));
    }

    // 2. FORM THÊM MỚI (Trỏ về users.form)
    public function create()
    {
        $user = Auth::user();

        // Lấy danh sách công ty tùy theo quyền
        if ($user->role == 1) {
            $companies = Company::where('id', $user->company_id)->get();
        } else {
            $companies = Company::select('id', 'name')->get();
        }

        // Không truyền biến $user -> Form hiểu là THÊM MỚI
        return view('users.form', compact('companies'));
    }

    // 3. LƯU NHÂN VIÊN MỚI (Có xử lý Avatar)
    public function store(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'company_id' => 'required',
            'base_salary' => 'required|numeric',
            'avatar' => 'nullable|image|max:2048', // Validate ảnh
        ]);

        $data = $request->all();
        $data['password'] = Hash::make($request->password);

        // Xử lý upload ảnh (Nếu có)
        if ($request->hasFile('avatar')) {
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        // Bảo mật: Nếu là Quản lý, ép buộc company_id là của chính họ
        if ($user->role == 1) {
            $data['company_id'] = $user->company_id;
            // Không cho tạo Admin
            if (isset($data['role']) && $data['role'] == 0) {
                $data['role'] = 2; 
            }
        }

        User::create($data);

        return redirect()->route('users.index')->with('success', 'Thêm nhân viên thành công.');
    }

    // 4. FORM SỬA (Trỏ về users.form)
    public function edit(string $id)
    {
        $targetUser = User::findOrFail($id);
        $currentUser = Auth::user();

        // BẢO MẬT
        if ($currentUser->role == 1 && $targetUser->company_id != $currentUser->company_id) {
            abort(403, 'Bạn không có quyền sửa nhân viên của công ty khác.');
        }

        if ($currentUser->role == 1) {
            $companies = Company::where('id', $currentUser->company_id)->get();
        } else {
            $companies = Company::select('id', 'name')->get();
        }

        // Truyền biến 'user' -> Form hiểu là SỬA
        return view('users.form', [
            'user' => $targetUser, 
            'companies' => $companies
        ]);
    }
    
    // 5. CẬP NHẬT (Có xử lý Avatar và Xóa ảnh cũ)
    public function update(Request $request, string $id)
    {
        $targetUser = User::findOrFail($id);
        $currentUser = Auth::user();

        if ($currentUser->role == 1 && $targetUser->company_id != $currentUser->company_id) {
            abort(403, 'Bạn không có quyền cập nhật nhân viên này.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$id,
            'base_salary' => 'required|numeric',
            'avatar' => 'nullable|image|max:2048',
        ]);

        $data = $request->all();

        // Xử lý mật khẩu
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        } else {
            unset($data['password']); // Bỏ password khỏi mảng data để không bị ghi đè thành null
        }

        // Xử lý Avatar: Upload ảnh mới và xóa ảnh cũ
        if ($request->hasFile('avatar')) {
            // Xóa ảnh cũ nếu có
            if ($targetUser->avatar) {
                Storage::disk('public')->delete($targetUser->avatar);
            }
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        // Bảo mật Role
        if ($currentUser->role == 1) {
            $data['company_id'] = $currentUser->company_id;
            if (isset($data['role']) && $data['role'] == 0) {
                $data['role'] = 2; 
            }
        }

        $targetUser->update($data);

        return redirect()->route('users.index')->with('success', 'Cập nhật thông tin thành công.');
    }

    // 6. XÓA (Có xóa file ảnh)
    public function destroy(string $id)
    {
        $targetUser = User::findOrFail($id);
        $currentUser = Auth::user();

        if ($currentUser->role == 1 && $targetUser->company_id != $currentUser->company_id) {
            abort(403, 'Bạn không có quyền xóa nhân viên này.');
        }

        if ($currentUser->id == $targetUser->id) {
            return back()->with('error', 'Bạn không thể tự xóa tài khoản của chính mình.');
        }

        // Xóa ảnh đại diện khỏi ổ cứng trước khi xóa user (để tiết kiệm dung lượng)
        if ($targetUser->avatar) {
            Storage::disk('public')->delete($targetUser->avatar);
        }

        $targetUser->delete();

        return redirect()->route('users.index')->with('success', 'Đã xóa nhân viên thành công.');
    }
}