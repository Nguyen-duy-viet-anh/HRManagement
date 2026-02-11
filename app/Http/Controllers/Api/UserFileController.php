<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UserFileController extends Controller
{
    // POST /api/user-files/list
    public function index(Request $request)
    {
        $query = UserFile::with('user')->orderByDesc('created_at');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        return apiSuccess(
            $query->paginate(20),
            'Danh sách tài liệu'
        );
    }

    // POST /api/user-files/show
    public function show(Request $request)
    {
        $request->validate([
            'id' => 'required|uuid'
        ]);

        $file = UserFile::with('user')->find($request->id);

        if (!$file) {
            return apiError('Không tìm thấy tài liệu', 404);
        }

        return apiSuccess($file, 'Chi tiết tài liệu');
    }

    // POST /api/user-files/upload
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|uuid|exists:users,id',
            'file'    => 'required|file|max:10240', // Tối đa 10MB
            'type'    => 'nullable|string|max:50',   // cccd, cv, hop_dong...
        ]);

        $uploadedFile = $request->file('file');
        $path = $uploadedFile->store('user_files', 'public');

        $userFile = UserFile::create([
            'user_id'       => $request->user_id,
            'file_path'     => $path,
            'original_name' => $uploadedFile->getClientOriginalName(),
            'type'          => $request->type ?? 'document',
        ]);

        return apiSuccess($userFile, 'Upload tài liệu thành công', 201);
    }

    // POST /api/user-files/update
    public function update(Request $request)
    {
        $data = $request->validate([
            'id'   => 'required|uuid',
            'type' => 'sometimes|required|string|max:50',
        ]);

        $file = UserFile::find($data['id']);

        if (!$file) {
            return apiError('Không tìm thấy tài liệu', 404);
        }

        $file->update($data);

        return apiSuccess($file, 'Cập nhật tài liệu thành công');
    }

    // POST /api/user-files/delete
    public function destroy(Request $request)
    {
        $request->validate([
            'id' => 'required|uuid'
        ]);

        $file = UserFile::find($request->id);

        if (!$file) {
            return apiError('Không tìm thấy tài liệu', 404);
        }

        // Xóa file vật lý trên storage
        Storage::disk('public')->delete($file->file_path);

        $file->delete();

        return apiSuccess(null, 'Xóa tài liệu thành công');
    }
}
