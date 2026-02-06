<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function index(Request $request)
    {
        $query = Company::query()->orderByDesc('id');

        if ($request->filled('search')) {
        $query->where('name', 'like', '%' . $request->search . '%');
    }

        return apiSuccess(
            $query->paginate(20),
            'Danh sách công ty'
        );
    }

    public function show(Request $request)
{
    $request->validate([
        'id' => 'required|string'
    ]);

    $company = Company::find($request->id);

    if (!$company) {
        return apiError('Không tìm thấy công ty', 404);
    }

    return apiSuccess($company, 'Chi tiết công ty');
}

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'nullable|email|unique:companies,email',
            'address' => 'nullable|string|max:255',
            'phone'   => 'nullable|string|max:50',
            'status'  => 'required|in:0,1'
        ]);

        return apiSuccess(
            Company::create($data),
            'Tạo công ty thành công',
            201
        );
    }

    // PUT /api/companies/{id}


    public function update(Request $request)
    {
        $data = $request->validate([
            'id'      => 'required|string',
            'name'    => 'sometimes|required|string|max:255',
            'email'   => 'nullable|email|unique:companies,email,' . $request->id,
            'address' => 'nullable|string|max:255',
            'phone'   => 'nullable|string|max:50',
            'status'  => 'sometimes|required|in:0,1'
        ]);

        $company = Company::find($data['id']);

        if (!$company) {
            return apiError('Không tìm thấy công ty', 404);
        }

        $company->update($data);

        return apiSuccess($company, 'Cập nhật thành công');
    }

    // DELETE /api/companies/{id}
    public function destroy(Request $request)
    {
        $request->validate([
            'id' => 'required|string'
        ]);

        $company = Company::find($request->id);

        if (!$company) {
            return apiError('Không tìm thấy công ty', 404);
        }

        $company->delete();

        return apiSuccess(null, 'Xóa công ty thành công');
    }
}
