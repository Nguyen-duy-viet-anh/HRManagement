@extends('layout')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Cấu hình mệnh giá phiếu ăn</h5>
                    {{-- <a href="{{ route('lunch.index') }}" class="btn btn-sm btn-secondary">Quay lại</a> --}}
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert-success">{{ session('success') }}</div>
                    @endif

                    {{-- Form thêm mới --}}
                    <form action="{{ route('lunch.store_price') }}" method="POST" class="row g-3 mb-4 align-items-end">
                        @csrf
                        <div class="col-auto">
                            <label class="form-label">Thêm mệnh giá mới (VNĐ)</label>
                            <input type="number" name="price" class="form-control" placeholder="VD: 40000" required min="1000" step="1000">
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary">Thêm</button>
                        </div>
                    </form>

                    {{-- Danh sách --}}
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Mệnh giá</th>
                                <th width="100" class="text-center">Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($prices as $price)
                            <tr>
                                <td class="fw-bold">{{ number_format($price->price) }} VNĐ</td>
                                <td class="text-center">
                                    <form action="{{ route('lunch.delete_price', $price->id) }}" method="POST" onsubmit="return confirm('Bạn có chắc muốn xóa mức giá này?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-danger">Xóa</button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection