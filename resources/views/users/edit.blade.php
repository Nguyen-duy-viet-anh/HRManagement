@extends('layout')

@section('content')
<div class="card shadow">
    <div class="card-header bg-primary text-white">
        <h5 class="m-0">Chỉnh sửa nhân viên: {{ $user->name }}</h5>
    </div>
    <div class="card-body">
        
        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('users.update', $user->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="fw-bold">Họ tên</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="fw-bold">Email</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="fw-bold">Công ty</label>
                    @if(Auth::user()->role == 1)
                        <input type="text" class="form-control bg-light" value="{{ $user->company->name ?? 'N/A' }}" readonly>
                        <input type="hidden" name="company_id" value="{{ $user->company_id }}">
                    @else
                        <select name="company_id" class="form-select" required>
                            @foreach($companies as $company)
                                <option value="{{ $company->id }}" {{ $user->company_id == $company->id ? 'selected' : '' }}>
                                    {{ $company->name }}
                                </option>
                            @endforeach
                        </select>
                    @endif
                </div>

                <div class="col-md-6 mb-3">
                    <label class="fw-bold">Vai trò hệ thống</label>
                    <select name="role" class="form-select" required>
                        @if(Auth::user()->role == 0)
                            <option value="0" {{ $user->role == 0 ? 'selected' : '' }}>Admin hệ thống</option>
                        @endif
                        
                        <option value="1" {{ $user->role == 1 ? 'selected' : '' }}>Quản lý Công ty</option>
                        <option value="2" {{ $user->role == 2 ? 'selected' : '' }}>Nhân viên</option>
                    </select>
                    @if(Auth::user()->role == 1)
                        <small class="text-muted">* Bạn không thể bổ nhiệm Admin hệ thống.</small>
                    @endif
                </div>

                <div class="col-md-6 mb-3">
                    <label class="fw-bold">Mật khẩu mới (Bỏ trống nếu giữ nguyên)</label>
                    <input type="password" name="password" class="form-control" placeholder="******">
                </div>

                <div class="col-md-6 mb-3">
                    <label class="fw-bold">Lương cơ bản</label>
                    <input type="number" name="base_salary" class="form-control" value="{{ old('base_salary', $user->base_salary) }}">
                </div>

                <div class="col-md-4 mb-3">
                    <label>Ngày sinh</label>
                    <input type="date" name="birthday" class="form-control" value="{{ old('birthday', $user->birthday) }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label>Giới tính</label>
                    <select name="gender" class="form-select">
                        <option value="male" {{ $user->gender == 'male' ? 'selected' : '' }}>Nam</option>
                        <option value="female" {{ $user->gender == 'female' ? 'selected' : '' }}>Nữ</option>
                        <option value="other" {{ $user->gender == 'other' ? 'selected' : '' }}>Khác</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label>Số điện thoại</label>
                    <input type="text" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}">
                </div>
                
                <div class="col-12 mb-3">
                    <label>Địa chỉ</label>
                    <input type="text" name="address" class="form-control" value="{{ old('address', $user->address) }}">
                </div>

                <div class="col-md-6 mb-3">
                    <label class="fw-bold">Trạng thái làm việc</label>
                    <select name="status" class="form-select">
                        <option value="1" {{ $user->status == 1 ? 'selected' : '' }}>Đang làm việc</option>
                        <option value="0" {{ $user->status == 0 ? 'selected' : '' }}>Đã nghỉ</option>
                    </select>
                </div>

                <div class="col-md-6 mb-3">
                    <label>Ảnh đại diện mới</label>
                    <input type="file" name="avatar" class="form-control">
                    @if($user->avatar)
                        <div class="mt-2">
                            <small class="text-muted">Ảnh hiện tại:</small><br>
                            <img src="{{ asset('storage/' . $user->avatar) }}" class="rounded border" width="60">
                        </div>
                    @endif
                </div>
            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-primary fw-bold">Lưu cập nhật</button>
                <a href="{{ route('users.index') }}" class="btn btn-secondary">Quay lại danh sách</a>
            </div>
        </form>
    </div>
</div>
@endsection