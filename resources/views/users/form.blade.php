@extends('layout')

@section('content')
@php
    $isEdit = isset($user);
    $title = $isEdit ? 'Chỉnh sửa nhân viên: ' . $user->name : 'Thêm nhân viên mới';
    $action = $isEdit ? route('users.update', $user->id) : route('users.store');
    
    // Lấy user đang đăng nhập để kiểm tra quyền
    $authUser = Auth::user();
@endphp

<div class="card shadow">
    <div class="card-header {{ $isEdit ? 'bg-primary text-white' : '' }}">
        <h5 class="m-0 {{ $isEdit ? '' : 'text-primary' }}">{{ $title }}</h5>
    </div>
    <div class="card-body">
        
        {{-- Hiển thị lỗi Validate --}}
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ $action }}" method="POST" enctype="multipart/form-data">
            @csrf
            @if($isEdit)
                @method('PUT')
            @endif
            
            {{-- PHẦN 1: THÔNG TIN TÀI KHOẢN --}}
            <div class="row">
                <div class="col-md-12 mb-3">
                    <h6 class="text-secondary border-bottom pb-2">1. Thông tin tài khoản</h6>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="fw-bold">Họ và tên <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" 
                           value="{{ old('name', $user->name ?? '') }}" required>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="fw-bold">Email <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control" 
                           value="{{ old('email', $user->email ?? '') }}" required>
                </div>

                {{-- Mật khẩu: Bắt buộc khi tạo mới, Tùy chọn khi sửa --}}
                <div class="col-md-6 mb-3">
                    <label class="fw-bold">
                        Mật khẩu 
                        @if(!$isEdit) <span class="text-danger">*</span> @endif
                    </label>
                    <input type="password" name="password" class="form-control" 
                           placeholder="{{ $isEdit ? 'Bỏ trống nếu không đổi' : 'Tối thiểu 6 ký tự' }}"
                           {{ !$isEdit ? 'required' : '' }}>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="fw-bold">Công ty <span class="text-danger">*</span></label>
                    @if($authUser->role == 1) 
                        {{-- Nếu là Quản lý: Chỉ được xem, không được sửa công ty --}}
                        <input type="text" class="form-control bg-light" 
                               value="{{ $isEdit ? ($user->company->name ?? 'N/A') : $authUser->company->name }}" readonly>
                        {{-- Input ẩn để gửi dữ liệu đi --}}
                        <input type="hidden" name="company_id" 
                               value="{{ $isEdit ? $user->company_id : $authUser->company_id }}">
                    @else
                        {{-- Nếu là Admin: Được chọn công ty thoải mái --}}
                        <select name="company_id" class="form-select" required>
                            <option value="">-- Chọn công ty --</option>
                            @foreach($companies as $company)
                                <option value="{{ $company->id }}" 
                                    {{ (old('company_id', $user->company_id ?? '') == $company->id) ? 'selected' : '' }}>
                                    {{ $company->name }}
                                </option>
                            @endforeach
                        </select>
                    @endif
                </div>

                <div class="col-md-6 mb-3">
                    <label class="fw-bold">Vai trò</label>
                    <select name="role" class="form-select" required>
                        {{-- Admin hệ thống chỉ Admin mới bổ nhiệm được --}}
                        @if($authUser->role == 0)
                            <option value="0" {{ (old('role', $user->role ?? '') == 0) ? 'selected' : '' }}>Super Admin</option>
                        @endif
                        
                        <option value="1" {{ (old('role', $user->role ?? '') == 1) ? 'selected' : '' }}>Quản lý (Admin Công ty)</option>
                        <option value="2" {{ (old('role', $user->role ?? 2) == 2) ? 'selected' : '' }}>Nhân viên</option>
                    </select>
                </div>
                
                @if($isEdit)
                <div class="col-md-6 mb-3">
                    <label class="fw-bold">Trạng thái làm việc</label>
                    <select name="status" class="form-select">
                        <option value="1" {{ (old('status', $user->status ?? 1) == 1) ? 'selected' : '' }}>Đang làm việc</option>
                        <option value="0" {{ (old('status', $user->status ?? 1) == 0) ? 'selected' : '' }}>Đã nghỉ</option>
                    </select>
                </div>
                @endif

                {{-- PHẦN 2: THÔNG TIN CÁ NHÂN --}}
                <div class="col-md-12 mb-3 mt-3">
                    <h6 class="text-secondary border-bottom pb-2">2. Thông tin cá nhân</h6>
                </div>

                <div class="col-md-4 mb-3">
                    <label>Ngày sinh</label>
                    <input type="date" name="birthday" class="form-control" 
                           value="{{ old('birthday', $user->birthday ?? '') }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label>Giới tính</label>
                    <select name="gender" class="form-select">
                        <option value="male" {{ (old('gender', $user->gender ?? '') == 'male') ? 'selected' : '' }}>Nam</option>
                        <option value="female" {{ (old('gender', $user->gender ?? '') == 'female') ? 'selected' : '' }}>Nữ</option>
                        <option value="other" {{ (old('gender', $user->gender ?? '') == 'other') ? 'selected' : '' }}>Khác</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label>Số điện thoại</label>
                    <input type="text" name="phone" class="form-control" 
                           value="{{ old('phone', $user->phone ?? '') }}">
                </div>
                <div class="col-12 mb-3">
                    <label>Địa chỉ thường trú</label>
                    <textarea name="address" class="form-control" rows="2">{{ old('address', $user->address ?? '') }}</textarea>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label>Ảnh đại diện</label>
                    <input type="file" name="avatar" class="form-control">
                    @if($isEdit && $user->avatar)
                        <div class="mt-2">
                            <small class="text-muted">Ảnh hiện tại:</small><br>
                            <img src="{{ asset('storage/' . $user->avatar) }}" class="rounded border mt-1" width="60" height="60" style="object-fit: cover;">
                        </div>
                    @endif
                </div>

                {{-- PHẦN 3: LƯƠNG & HỢP ĐỒNG --}}
                <div class="col-md-12 mb-3 mt-3">
                    <h6 class="text-secondary border-bottom pb-2">3. Hợp đồng & Lương</h6>
                </div>

                <div class="col-md-6 mb-3">
                    <label>Ngày bắt đầu làm việc</label>
                    <input type="date" name="start_date" class="form-control" 
                           value="{{ old('start_date', $user->start_date ?? date('Y-m-d')) }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label>Lương cơ bản (VNĐ)</label>
                    <input type="number" name="base_salary" class="form-control" 
                           value="{{ old('base_salary', $user->base_salary ?? 5000000) }}">
                </div>
            </div>

            <div class="mt-4">
                <button class="btn {{ $isEdit ? 'btn-primary' : 'btn-success' }} fw-bold">
                    <i class="bi bi-save me-1"></i> {{ $isEdit ? 'Lưu cập nhật' : 'Tạo nhân viên mới' }}
                </button>
                <a href="{{ route('users.index') }}" class="btn btn-secondary ms-2">Quay lại danh sách</a>
            </div>
        </form>
    </div>
</div>
@endsection