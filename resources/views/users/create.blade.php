@extends('layout')

@section('content')
<div class="card shadow">
    <div class="card-header">
        <h5 class="m-0 text-primary">Thêm Nhân viên mới</h5>
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

        <form action="{{ route('users.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            
            <div class="row">
                <div class="col-md-12 mb-3">
                    <h6 class="text-secondary border-bottom pb-2">1. Thông tin tài khoản</h6>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label>Họ và tên <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label>Email <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label>Mật khẩu <span class="text-danger">*</span></label>
                    <input type="password" name="password" class="form-control" required placeholder="Tối thiểu 6 ký tự">
                </div>
                <div class="col-md-6 mb-3">
                    <label>Công ty <span class="text-danger">*</span></label>
                    <select name="company_id" class="form-select" required>
                        <option value="">-- Chọn công ty --</option>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label>Vai trò</label>
                    <select name="role" class="form-select">
                        <option value="2">Nhân viên</option>
                        <option value="1">Quản lý (Admin Công ty)</option>
                        <option value="0">Super Admin</option>
                    </select>
                </div>

                <div class="col-md-12 mb-3 mt-3">
                    <h6 class="text-secondary border-bottom pb-2">2. Thông tin cá nhân</h6>
                </div>

                <div class="col-md-4 mb-3">
                    <label>Ngày sinh</label>
                    <input type="date" name="birthday" class="form-control">
                </div>
                <div class="col-md-4 mb-3">
                    <label>Giới tính</label>
                    <select name="gender" class="form-select">
                        <option value="male">Nam</option>
                        <option value="female">Nữ</option>
                        <option value="other">Khác</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label>Số điện thoại</label>
                    <input type="text" name="phone" class="form-control">
                </div>
                <div class="col-12 mb-3">
                    <label>Địa chỉ thường trú</label>
                    <textarea name="address" class="form-control" rows="2"></textarea>
                </div>
                <div class="col-md-6 mb-3">
                    <label>Ảnh đại diện</label>
                    <input type="file" name="avatar" class="form-control">
                </div>

                <div class="col-md-12 mb-3 mt-3">
                    <h6 class="text-secondary border-bottom pb-2">3. Hợp đồng & Lương</h6>
                </div>

                <div class="col-md-6 mb-3">
                    <label>Ngày bắt đầu làm việc</label>
                    <input type="date" name="start_date" class="form-control" value="{{ date('Y-m-d') }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label>Lương cơ bản (VNĐ)</label>
                    <input type="number" name="base_salary" class="form-control" value="5000000">
                </div>
            </div>

            <button class="btn btn-success mt-3">Lưu nhân viên</button>
            <a href="{{ route('users.index') }}" class="btn btn-secondary mt-3">Quay lại</a>
        </form>
    </div>
</div>
@endsection