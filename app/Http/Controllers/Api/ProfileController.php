<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    // POST /api/profile/show
    public function show(Request $request)
    {
        $user = User::with('files', 'company')->find($request->user()->id);
        return apiSuccess($user, 'Thông tin hồ sơ cá nhân');
    }

    // POST /api/profile/update
    public function update(Request $request)
    {
        $user = User::find($request->user()->id);

        $data = $request->validate([
            'name'     => 'sometimes|required|string|max:255',
            'email'    => 'sometimes|required|email|unique:users,email,' . $user->id,
            'phone'    => 'nullable|string|max:15',
            'address'  => 'nullable|string|max:500',
            'birthday' => 'nullable|date',
            'gender'   => 'sometimes|required|in:male,female,other',
            'avatar'   => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'password' => 'nullable|min:6|confirmed',
        ]);

        // Cập nhật thông tin cơ bản
        $user->fill($request->only(['name', 'email', 'phone', 'address', 'birthday', 'gender']));

        // Xử lý avatar
        if ($request->hasFile('avatar')) {
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }

            $file = $request->file('avatar');
            $filename = 'avatar_' . time() . '.' . $file->getClientOriginalExtension();

            if ($user->company_id) {
                $folderPath = "companies/{$user->company_id}/users/{$user->id}";
            } else {
                $folderPath = "avatars";
            }

            $path = $file->storeAs($folderPath, $filename, 'public');
            $user->avatar = $path;
        }

        // Đổi mật khẩu
        if ($request->filled('password')) {
            $user->password = bcrypt($request->password);
        }

        $user->save();

        return apiSuccess($user, 'Cập nhật hồ sơ thành công');
    }

    // POST /api/profile/colleagues
    public function colleagues(Request $request)
    {
        $user = $request->user();
        $companyId = $user->company_id;

        if (!$companyId) {
            return apiSuccess([], 'Không thuộc công ty nào');
        }

        $colleagues = User::where('company_id', $companyId)
            ->select('id', 'name', 'email', 'avatar', 'role')
            ->paginate(20);

        return apiSuccess($colleagues, 'Danh sách đồng nghiệp');
    }

    // POST /api/profile/files
    public function allFiles(Request $request)
    {
        $user = $request->user();

        $files = UserFile::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return apiSuccess($files, 'Danh sách tài liệu của tôi');
    }
}
