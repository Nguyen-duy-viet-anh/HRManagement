<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    // 1. XEM DANH SÁCH (Đã phân quyền)
    public function index(Request $request)
{
    $user = Auth::user();
    $search = $request->input('search');
    $company_id = $request->input('company_id'); // Lấy id công ty từ request

    $query = User::with('company');

    // Phân quyền
    if ($user->role == 1) {
        $query->where('company_id', $user->company_id);
    } else {
        // Nếu là Admin và có chọn công ty cụ thể
        if ($company_id) {
            $query->where('company_id', $company_id);
        }
    }

    // Tìm kiếm theo tên/email
    if ($search) {
        $query->where(function ($q) use ($search) {
            $q->where('name', 'LIKE', "%{$search}%")
              ->orWhere('email', 'LIKE', "%{$search}%");
        });
    }

    $users = $query->orderBy('name', 'asc')->paginate(20);
    
    // Lấy danh sách công ty cho Admin lọc (chỉ lấy ID và Name để nhẹ máy)
    $companies = Company::select('id', 'name')->get();

    return view('users.index', compact('users', 'search', 'companies', 'company_id'));
}

    // 2. FORM THÊM MỚI (Chặn chọn công ty lung tung)
    public function create()
    {
        $user = Auth::user();

        if ($user->role == 1) {
            // Role 1: Chỉ gửi sang View đúng 1 công ty của họ
            $companies = Company::where('id', $user->company_id)->get();
        } else {
            // Role 0: Gửi sang tất cả công ty để chọn
            $companies = Company::all();
        }

        return view('users.create', compact('companies'));
    }

    // 3. LƯU NHÂN VIÊN MỚI (Tự động gán công ty)
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            // Nếu là Admin thì bắt buộc chọn công ty, nếu là Quản lý thì thôi
            'company_id' => Auth::user()->role == 0 ? 'required' : '', 
            'base_salary' => 'required|numeric'
        ]);

        $data = $request->all();
        $data['password'] = Hash::make($request->password);

        // LOGIC QUAN TRỌNG:
        // Nếu là Quản lý (Role 1) -> Tự động điền ID công ty của họ vào
        if (Auth::user()->role == 1) {
            $data['company_id'] = Auth::user()->company_id;
            
            // Đảm bảo không thể tạo user có quyền Admin (Role 0)
            // Chỉ cho tạo Nhân viên (2) hoặc Quản lý phụ (1)
            if ($request->role == 0) {
                $data['role'] = 2; // Ép về nhân viên nếu cố tình hack
            }
        }

        User::create($data);

        return redirect()->route('users.index')->with('success', 'Thêm nhân viên thành công.');
    }
    // =========================================================
    // 4. HIỂN THỊ FORM SỬA (EDIT)
    // =========================================================
    public function edit(string $id)
    {
        $targetUser = User::findOrFail($id);
        $currentUser = Auth::user();

        // BẢO MẬT: Nếu là Quản lý (Role 1) mà cố tình sửa nhân viên công ty khác -> Chặn
        if ($currentUser->role == 1 && $targetUser->company_id != $currentUser->company_id) {
            abort(403, 'Bạn không có quyền sửa nhân viên của công ty khác.');
        }

        // Chuẩn bị danh sách công ty
        if ($currentUser->role == 1) {
            $companies = \App\Models\Company::where('id', $currentUser->company_id)->get();
        } else {
            $companies = \App\Models\Company::all();
        }

        // SỬA DÒNG NÀY: Đổi tên key thành 'user'
        return view('users.edit', [
            'user' => $targetUser, 
            'companies' => $companies
        ]);
    }
    // =========================================================
    // 5. LƯU CẬP NHẬT (UPDATE)
    // =========================================================
    public function update(Request $request, string $id)
    {
        $targetUser = User::findOrFail($id);
        $currentUser = Auth::user();

        // BẢO MẬT: Kiểm tra quyền lần nữa
        if ($currentUser->role == 1 && $targetUser->company_id != $currentUser->company_id) {
            abort(403, 'Bạn không có quyền cập nhật nhân viên này.');
        }

        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email,'.$id, // Cho phép trùng email chính mình
            'base_salary' => 'required|numeric'
        ]);

        $data = $request->all();

        // Nếu người dùng nhập mật khẩu mới thì mới cập nhật, không thì giữ nguyên
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        } else {
            unset($data['password']);
        }

        // Logic ép buộc công ty (giống hàm store)
        if ($currentUser->role == 1) {
            $data['company_id'] = $currentUser->company_id;
            // Không cho Quản lý tự ý thăng chức cho nhân viên thành Admin
            if ($request->role == 0) {
                $data['role'] = 2; 
            }
        }

        $targetUser->update($data);

        return redirect()->route('users.index')->with('success', 'Cập nhật thông tin thành công.');
    }

    // =========================================================
    // 6. XÓA NHÂN VIÊN (DESTROY)
    // =========================================================
    public function destroy(string $id)
    {
        $targetUser = User::findOrFail($id);
        $currentUser = Auth::user();

        // BẢO MẬT: Chặn xóa nhân viên công ty khác
        if ($currentUser->role == 1 && $targetUser->company_id != $currentUser->company_id) {
            abort(403, 'Bạn không có quyền xóa nhân viên này.');
        }

        // Không cho phép tự xóa chính mình
        if ($currentUser->id == $targetUser->id) {
            return back()->with('error', 'Bạn không thể tự xóa tài khoản của chính mình.');
        }

        $targetUser->delete();

        return redirect()->route('users.index')->with('success', 'Đã xóa nhân viên thành công.');
    }
}