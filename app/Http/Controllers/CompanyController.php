<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Company;

class CompanyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
    // Lấy 10 công ty một trang, sắp xếp mới nhất lên đầu
    // withCount('users'): Đếm luôn xem công ty đó có bao nhiêu nhân viên (Laravel tự làm, rất tiện)
    $companies = Company::withCount('users')->latest()->paginate(10);

    // Trả về view và gửi kèm biến $companies sang đó
    return view('companies.index', compact('companies'));
    }
    // 2. Hiện form thêm mới
    public function create()
    {
        return view('companies.create');
    }

    // 3. Xử lý lưu dữ liệu thêm mới
    public function store(Request $request)
    {
        // Validate (Kiểm tra dữ liệu đầu vào)
        $request->validate([
            'name' => 'required', // Bắt buộc nhập
            'email' => 'nullable|email|unique:companies,email', // Email không được trùng
            'standard_working_days' => 'required|integer|min:1|max:31',
        ]);

        // Lưu vào database
        Company::create($request->all());

        return redirect()->route('companies.index')->with('success', 'Đã thêm công ty mới!');
    }

   // Hàm hiển thị form sửa
    public function edit($id)
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        
        // BẢO MẬT: Nếu là Quản lý (Role 1)
        if ($user->role == 1) {
            // Nếu ID trên URL khác ID công ty của họ -> Chặn ngay
            if ($id != $user->company_id) {
                abort(403, 'Bạn không có quyền chỉnh sửa công ty khác.');
            }
        }

        $company = Company::findOrFail($id);
        return view('companies.edit', compact('company'));
    }

    // Hàm lưu thông tin sửa
    public function update(Request $request, $id)
    {
        $user = \Illuminate\Support\Facades\Auth::user();

        // BẢO MẬT: Kiểm tra lại lần nữa khi bấm nút Lưu
        if ($user->role == 1 && $id != $user->company_id) {
            abort(403, 'Bạn không có quyền chỉnh sửa công ty khác.');
        }

        $request->validate([
            'name' => 'required',
            'email' => 'required|email',
            // Các validate khác tùy bạn...
        ]);

        $company = Company::findOrFail($id);
        $company->update($request->all());

        // Nếu là Admin thì quay về danh sách, nếu là Quản lý thì quay lại form sửa (vì họ ko xem đc danh sách)
        if ($user->role == 0) {
            return redirect()->route('companies.index')->with('success', 'Cập nhật thành công');
        } else {
            return back()->with('success', 'Cập nhật thông tin công ty thành công');
        }
    }

    // 6. Xóa công ty
    public function destroy(Company $company)
    {
        $company->delete();
        return redirect()->route('companies.index')->with('success', 'Đã xóa công ty!');
    }

}
