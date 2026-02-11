<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LunchPrice;
use Illuminate\Http\Request;

class LunchPriceController extends Controller
{
    // POST /api/lunch-prices/list
    public function index()
    {
        return apiSuccess(
            LunchPrice::orderBy('price')->get(),
            'Danh sách giá cơm'
        );
    }

    // POST /api/lunch-prices/show
    public function show(Request $request)
    {
        $request->validate([
            'id' => 'required|integer'
        ]);

        $price = LunchPrice::find($request->id);

        if (!$price) {
            return apiError('Không tìm thấy mức giá', 404);
        }

        return apiSuccess($price, 'Chi tiết mức giá');
    }

    // POST /api/lunch-prices/create
    public function store(Request $request)
    {
        $data = $request->validate([
            'price' => 'required|integer|min:0|unique:lunch_prices,price'
        ]);

        return apiSuccess(
            LunchPrice::create($data),
            'Tạo mức giá thành công',
            201
        );
    }

    // POST /api/lunch-prices/update
    public function update(Request $request)
    {
        $data = $request->validate([
            'id'    => 'required|integer',
            'price' => 'required|integer|min:0|unique:lunch_prices,price,' . $request->id
        ]);

        $price = LunchPrice::find($data['id']);

        if (!$price) {
            return apiError('Không tìm thấy mức giá', 404);
        }

        $price->update($data);

        return apiSuccess($price, 'Cập nhật mức giá thành công');
    }

    // POST /api/lunch-prices/delete
    public function destroy(Request $request)
    {
        $request->validate([
            'id' => 'required|integer'
        ]);

        $price = LunchPrice::find($request->id);

        if (!$price) {
            return apiError('Không tìm thấy mức giá', 404);
        }

        $price->delete();

        return apiSuccess(null, 'Xóa mức giá thành công');
    }
}
