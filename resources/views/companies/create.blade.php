@extends('layout')

@section('content')
<div class="card shadow">
    <div class="card-header">
        <h5 class="m-0 text-primary">Thêm công ty mới</h5>
    </div>
    <div class="card-body">
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('companies.store') }}" method="POST">
            @csrf
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label>Tên công ty <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required placeholder="Nhập tên công ty">
                </div>
                <div class="col-md-6 mb-3">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" placeholder="contact@company.com">
                </div>
                <div class="col-md-6 mb-3">
                    <label>Số điện thoại</label>
                    <input type="text" name="phone" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label>Ngày công chuẩn (để tính lương)</label>
                    <input type="number" name="standard_working_days" class="form-control" value="26">
                    <small class="text-muted">Thường là 24 hoặc 26 ngày/tháng</small>
                </div>
                <div class="col-12 mb-3">
                    <label>Địa chỉ</label>
                    <textarea name="address" class="form-control" rows="2"></textarea>
                </div>
            </div>

            <button class="btn btn-success">Lưu lại</button>
            <a href="{{ route('companies.index') }}" class="btn btn-secondary">Hủy</a>
        </form>
    </div>
</div>
@endsection