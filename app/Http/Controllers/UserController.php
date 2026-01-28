<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 
use Illuminate\Support\Str;



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

    // 3. LƯU NHÂN VIÊN MỚI 
    public function store(Request $request)
    {
        $currentUser = Auth::user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'company_id' => 'nullable',
            'base_salary' => 'required|numeric',
            'avatar' => 'nullable|image|max:2048',
        ]);

        $data = $request->all();
        $data['password'] = Hash::make($request->password);

        // Tạo UUID
        $newUserId = (string) Str::uuid();
        $data['id'] = $newUserId;

        // Xử lý Role 1 (Quản lý) chỉ được thêm người vào công ty mình
        if ($currentUser->role == 1) {
            $data['company_id'] = $currentUser->company_id;
        }

        // --- XỬ LÝ ẢNH ---
        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            $filename = 'avatar_' . time() . '.' . $file->getClientOriginalExtension();

            // LOGIC PHÂN LOẠI:
            if (!empty($data['company_id'])) {
                // A. Có công ty -> Lưu vào folder của công ty đó
                $folderPath = "companies/{$data['company_id']}/users/{$newUserId}";
            } else {
                // B. Admin Tổng (ko có công ty) -> Lưu vào 'avatars' chung chung (đơn giản)
                $folderPath = "avatars";
            }

            $path = $file->storeAs($folderPath, $filename, 'public');
            $data['avatar'] = $path;
        }

        User::create($data);

        return redirect()->route('users.index')->with('success', 'Thêm nhân viên thành công.');
    }
    // 4. FORM SỬA
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

        return view('users.form', [
            'user' => $targetUser, 
            'companies' => $companies
        ]);
    }
    
    // 5. CẬP NHẬT 
    public function update(Request $request, string $id)
    {
        $targetUser = User::findOrFail($id);
        $currentUser = Auth::user();

        // 1. Check quyền
        if ($currentUser->role == 1 && $targetUser->company_id != $currentUser->company_id) {
            abort(403, 'Bạn không có quyền cập nhật nhân viên này.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'base_salary' => 'required|numeric',
            'avatar' => 'nullable|image|max:2048',
        ]);

        $data = $request->all();

        // 2. Xử lý Password
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        } else {
            unset($data['password']);
        }

        // 3. Xử lý Role 1 (Quản lý) - Bắt buộc set lại company_id để tránh hack form
        if ($currentUser->role == 1) {
            $data['company_id'] = $currentUser->company_id;
            // Nếu quản lý cố tình set role=0 (Admin) -> ép về 2 (Nhân viên)
            if (isset($data['role']) && $data['role'] == 0) {
                $data['role'] = 2; 
            }
        }

        // 4. Xử lý Ảnh (Logic phân loại thư mục bạn đã làm đúng)
        if ($request->hasFile('avatar')) {
            if ($targetUser->avatar && Storage::disk('public')->exists($targetUser->avatar)) {
                Storage::disk('public')->delete($targetUser->avatar);
            }

            // Ưu tiên lấy company_id mới (nếu có trong data), nếu không lấy cái cũ
            $companyId = $data['company_id'] ?? $targetUser->company_id;

            if ($companyId) {
                $folderPath = "companies/{$companyId}/users/{$targetUser->id}";
            } else {
                $folderPath = "avatars";
            }

            $file = $request->file('avatar');
            $filename = 'avatar_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs($folderPath, $filename, 'public');
            $data['avatar'] = $path;
        }

        // 5. Update 1 lần duy nhất
        $targetUser->update($data);

        return redirect()->route('users.index')->with('success', 'Cập nhật thành công.');
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
        if ($targetUser->company_id) {
            // Nếu là nhân viên công ty -> Xóa cả folder riêng của họ trong công ty
            Storage::disk('public')->deleteDirectory("companies/{$targetUser->company_id}/users/{$targetUser->id}");
        } else {
            // Nếu là Admin Tổng -> Chỉ xóa file ảnh (vì folder 'avatars' dùng chung, không xóa folder được)
            if ($targetUser->avatar) {
                Storage::disk('public')->delete($targetUser->avatar);
            }
        }

        $targetUser->delete();

        return redirect()->route('users.index')->with('success', 'Đã xóa nhân viên thành công.');
    }
}