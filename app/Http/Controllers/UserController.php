<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Company;
use App\Models\UserFile;
use Illuminate\Http\Request;
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

        if ($user->role == 1) {
            $query->where('company_id', $user->company_id);
        } else {
            if ($company_id) {
                $query->where('company_id', $company_id);
            }
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        $users = $query->orderBy('id', 'desc')->Paginate(50); 
        
        $companies = [];
        if ($user->role == 0) {
            $companies = Company::select('id', 'name')->get();
        }

        return view('users.index', compact('users', 'search', 'companies', 'company_id'));
    }

    // 2. FORM THÊM MỚI
    public function create()
    {
        $user = Auth::user();
        if ($user->role == 1) {
            $companies = Company::where('id', $user->company_id)->get();
        } else {
            $companies = Company::select('id', 'name')->get();
        }
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
            'documents.*' => 'nullable|file|max:5120' 
        ]);

        $data = $request->all();
        $data['password'] = bcrypt($request->password);
        
        $newUserId = (string) Str::uuid();
        $data['id'] = $newUserId;

        if ($currentUser->role == 1) {
            $data['company_id'] = $currentUser->company_id;
        }

        // --- A. XỬ LÝ AVATAR ---
        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            $filename = 'avatar_' . time() . '.' . $file->getClientOriginalExtension();

            if (!empty($data['company_id'])) {
                $folderPath = "companies/{$data['company_id']}/users/{$newUserId}";
            } else {
                $folderPath = "avatars";
            }

            $path = $file->storeAs($folderPath, $filename, 'public');
            $data['avatar'] = $path;
        }

        // Tạo User trước
        $user = User::create($data);

        // --- B. XỬ LÝ FILE ĐÍNH KÈM (CCCD, CV...) ---
        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $file) {
                $docName = time() . '_' . $file->getClientOriginalName();
                
                // Xác định folder lưu file
                if ($user->company_id) {
                    $docFolder = "companies/{$user->company_id}/users/{$user->id}/documents";
                } else {
                    $docFolder = "users/{$user->id}/documents";
                }

                $docPath = $file->storeAs($docFolder, $docName, 'public');

                // Lưu vào bảng user_files
                UserFile::create([
                    'user_id' => $user->id,
                    'file_path' => $docPath,
                    'original_name' => $file->getClientOriginalName(),
                    'type' => 'document'
                ]);
            }
        }

        return redirect()->route('users.index')->with('success', 'Thêm nhân viên thành công.');
    }

    // 4. FORM SỬA
    public function edit(string $id)
    {
        $targetUser = User::with('files')->findOrFail($id); // Eager load files
        $currentUser = Auth::user();

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

        if ($currentUser->role == 1 && $targetUser->company_id != $currentUser->company_id) {
            abort(403, 'Bạn không có quyền cập nhật nhân viên này.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'base_salary' => 'required|numeric',
            'avatar' => 'nullable|image|max:2048',
            'documents.*' => 'nullable|file|max:5120'
        ]);

        $data = $request->all();

        if ($request->filled('password')) {
            $data['password'] = bcrypt($request->password);
        } else {
            unset($data['password']);
        }

        // Bảo mật Role
        if ($currentUser->role == 1) {
            $data['company_id'] = $currentUser->company_id;
            if (isset($data['role']) && $data['role'] == 0) {
                $data['role'] = 2; 
            }
        }

        // --- A. XỬ LÝ AVATAR ---
        if ($request->hasFile('avatar')) {
            if ($targetUser->avatar && Storage::disk('public')->exists($targetUser->avatar)) {
                Storage::disk('public')->delete($targetUser->avatar);
            }

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

        // Cập nhật thông tin User
        $targetUser->update($data);

        // --- B. XỬ LÝ UPLOAD THÊM FILE ---
        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $file) {
                $docName = time() . '_' . $file->getClientOriginalName();
                
                $cId = $targetUser->company_id;
                if ($cId) {
                    $docFolder = "companies/{$cId}/users/{$targetUser->id}/documents";
                } else {
                    $docFolder = "users/{$targetUser->id}/documents";
                }

                $docPath = $file->storeAs($docFolder, $docName, 'public');

                UserFile::create([
                    'user_id' => $targetUser->id,
                    'file_path' => $docPath,
                    'original_name' => $file->getClientOriginalName(),
                    'type' => 'document'
                ]);
            }
        }

        return redirect()->route('users.edit', $targetUser->id)->with('success', 'Cập nhật thành công.');
    }

    // 6. XÓA USER
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

        // Xóa folder chứa ảnh và tài liệu
        if ($targetUser->company_id) {
            Storage::disk('public')->deleteDirectory("companies/{$targetUser->company_id}/users/{$targetUser->id}");
        } else {
            // Admin tổng: Xóa ảnh avatar (nếu có) và xóa folder documents riêng
            if ($targetUser->avatar) Storage::disk('public')->delete($targetUser->avatar);
            Storage::disk('public')->deleteDirectory("users/{$targetUser->id}");
        }

        $targetUser->delete();

        return redirect()->route('users.index')->with('success', 'Đã xóa nhân viên thành công.');
    }

    // 7. [MỚI] HÀM XÓA FILE ĐÍNH KÈM
    public function deleteFile($fileId)
    {
        $currentUser = Auth::user();

        // Tìm file và thông tin người sở hữu
        $file = UserFile::with('user')->findOrFail($fileId);

        // --- PHÂN QUYỀN ---

        // 1. Nếu là Admin Tổng (Role 0)
        if ($currentUser->role == 0) {
            // ĐỂ TRỐNG: Admin có quyền tối cao -> Cho đi thẳng xuống dưới để xóa
        } 
        
        // 2. Nếu là Quản lý công ty (Role 1)
        elseif ($currentUser->role == 1) {
            // Nếu file này của nhân viên công ty khác -> CHẶN
            if ($file->user && $file->user->company_id != $currentUser->company_id) {
                abort(403, 'Quản lý không được xóa file của công ty khác.');
            }
        } 
        
        // 3. Nếu là Nhân viên (Role 2)
        else {
            // Nếu không phải file của chính mình -> CHẶN
            if ($file->user_id != $currentUser->id) {
                abort(403, 'Bạn không được phép xóa file của người khác.');
            }
        }

        // --- THỰC HIỆN XÓA ---

        // Xóa file trong ổ cứng
        if (Storage::disk('public')->exists($file->file_path)) {
            Storage::disk('public')->delete($file->file_path);
        }

        // Xóa thông tin trong database
        $file->delete();

        return back()->with('success', 'Đã xóa tài liệu thành công.');
    }
    public function userFiles($id)
    {
        $currentUser = Auth::user();
        $targetUser = User::find($id);

        if (!$targetUser) {
            return back()->with('error', 'Nhân viên không tồn tại.');
        }

        // 1. Check quyền: Quản lý chỉ được xem file của nhân viên công ty mình
        if ($currentUser->role == 1 && $targetUser->company_id != $currentUser->company_id) {
            abort(403, 'Bạn không có quyền xem hồ sơ của nhân viên công ty khác.');
        }

        $files = $targetUser->files()->orderBy('created_at', 'desc')->paginate(50);

        return view('profile.files', compact('files', 'targetUser'));
    }
}