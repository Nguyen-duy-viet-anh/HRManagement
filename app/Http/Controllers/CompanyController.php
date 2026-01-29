<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Company;
use Illuminate\Support\Facades\Auth;

class CompanyController extends Controller
{
    public function index(Request $request)
{
    $search = $request->input('search');

    $companies = Company::query()
        ->when($search, function($query, $search) {
            return $query->where('name', 'LIKE', "%{$search}%");
        })
        ->withCount('users') 
        ->orderBy('id', 'asc')
        ->paginate(10);
        
    return view('companies.index', compact('companies', 'search'));
}
    // 1. Hiển thị form Thêm mới
    public function create()
    {
        // Thay đổi: Trỏ về view 'companies.form'
        // Không truyền biến $company -> View sẽ tự hiểu là đang THÊM MỚI
        return view('companies.form');
    }

    // 2. Xử lý lưu Thêm mới
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:companies,email',
            'standard_working_days' => 'required|integer|min:1|max:31',
            'phone' => 'nullable|string|max:20',
        ]);

        Company::create($request->all());

        return redirect()->route('companies.index')->with('success', 'Đã thêm công ty mới!');
    }

    // 3. Hiển thị form Chỉnh sửa
    public function edit($id)
    {
        $user = Auth::user();

        // BẢO MẬT: Chặn Quản lý (Role 1) sửa công ty người khác
        if ($user->role == 1 && $id != $user->company_id) {
            abort(403, 'Bạn không có quyền chỉnh sửa công ty này.');
        }

        $company = Company::findOrFail($id);

        // Thay đổi: Cũng trỏ về view 'companies.form'
        // NHƯNG có truyền biến $company -> View sẽ tự hiểu là đang SỬA
        return view('companies.form', compact('company'));
    }

    // 4. Xử lý lưu Cập nhật
    public function update(Request $request, $id)
    {
        $user = Auth::user();

        // BẢO MẬT: Kiểm tra lại quyền khi bấm nút Lưu
        if ($user->role == 1 && $id != $user->company_id) {
            abort(403, 'Bạn không có quyền chỉnh sửa công ty này.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            // QUAN TRỌNG: Kiểm tra trùng email nhưng trừ ID hiện tại ra
            // unique:bảng,cột,id_trừ_ra
            'email' => 'nullable|email|unique:companies,email,' . $id,
            'standard_working_days' => 'required|integer|min:1|max:31',
            'phone' => 'nullable|string|max:20',
        ]);

        $company = Company::findOrFail($id);
        $company->update($request->all());

        // Điều hướng thông minh:
        // - Admin (Role 0): Về danh sách để quản lý tiếp
        // - Quản lý (Role 1): Ở lại form sửa để xem lại thông tin (vì họ ko vào được trang index)
        if ($user->role == 0) {
            return redirect()->route('companies.index')->with('success', 'Cập nhật thành công');
        } else {
            return back()->with('success', 'Cập nhật thông tin công ty thành công');
        }
    }
}
