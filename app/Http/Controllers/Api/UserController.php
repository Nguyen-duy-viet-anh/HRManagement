<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    // GET /api/users
    public function index(Request $request)
    {
        $query = User::with('company')->orderByDesc('id');

        if ($request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        return apiSuccess(
            $query->paginate(20),
            'Danh sách nhân viên'
        );
    }

    // GET /api/users/{id}
    public function show(Request $request)
    {
        $request->validate([
            'id' => 'required|uuid'
        ]);

        $user = User::with('company')->find($request->id);

        if (!$user) {
            return apiError('Không tìm thấy nhân viên', 404);
        }

        return apiSuccess($user, 'Chi tiết nhân viên');
    }

    // POST /api/users
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'       => 'required|string|max:255',
            'email'      => 'required|email|unique:users,email',
            'company_id' => 'required|exists:companies,id',
            'status'     => 'required|in:0,1',
            'password' => 'required|string|min:6'
        ]);
        $data['password'] = bcrypt($data['password']);
        $user = User::create($data);

        return apiSuccess($user, 'Tạo nhân viên thành công', 201);
    }

    // PUT /api/users/{id}
    public function update(Request $request)
    {
        $data = $request->validate([
            'id'         => 'required|uuid',
            'name'       => 'sometimes|required|string|max:255',
            'email'      => 'sometimes|required|email|unique:users,email,' . $request->id,
            'company_id' => 'sometimes|required|exists:companies,id',
            'status'     => 'sometimes|required|in:0,1'
        ]);

        $user = User::find($data['id']);
        if (!$user) {
            return apiError('Không tìm thấy nhân viên', 404);
        }

        $user->update($data);

        return apiSuccess($user, 'Cập nhật nhân viên thành công');
    }

    // DELETE /api/users/{id}

    public function destroy(Request $request)
    {
        $request->validate([
            'id' => 'required|uuid'
        ]);

        $user = User::find($request->id);
        if (!$user) {
            return apiError('Không tìm thấy nhân viên', 404);
        }

        $user->delete();

        return apiSuccess(null, 'Xóa nhân viên thành công');
    }

    // POST /api/users/files
    public function userFiles(Request $request)
    {
        $request->validate([
            'user_id' => 'required|uuid|exists:users,id'
        ]);

        $currentUser = $request->user();
        $targetUser = User::find($request->user_id);

        // Quản lý chỉ xem file nhân viên công ty mình
        if ($currentUser->role == 1 && $targetUser->company_id != $currentUser->company_id) {
            return apiError('Không có quyền xem hồ sơ nhân viên công ty khác', 403);
        }

        $files = UserFile::where('user_id', $targetUser->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return apiSuccess([
            'user' => $targetUser,
            'files' => $files
        ], 'Danh sách tài liệu của nhân viên');
    }
}