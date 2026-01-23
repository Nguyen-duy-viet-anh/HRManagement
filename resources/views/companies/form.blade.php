@extends('layout')

@section('content')
@php
    // Kiểm tra có id công ty ko
    $isEdit = isset($company);
    
    $title = $isEdit ? 'Chỉnh sửa công ty: ' . $company->name : 'Thêm công ty mới';
    
    // Action của Form
    $action = $isEdit ? route('companies.update', $company->id) : route('companies.store');
@endphp

<div class="card shadow">
    <div class="card-header">
        <h5 class="m-0 text-primary">{{ $title }}</h5>
    </div>
    <div class="card-body">
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                     @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                     @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ $action }}" method="POST">
            @csrf
            {{-- Nếu là Sửa thì thêm method PUT --}}
            @if($isEdit)
                @method('PUT')
            @endif

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label>Tên công ty <span class="text-danger">*</span></label>
                    {{-- Sử dụng old() để giữ lại dữ liệu khi nhập lỗi --}}
                    <input type="text" name="name" class="form-control" 
                           value="{{ old('name', $company->name ?? '') }}" 
                           required placeholder="Nhập tên công ty">
                </div>
                
                <div class="col-md-6 mb-3">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" 
                           value="{{ old('email', $company->email ?? '') }}" 
                           placeholder="contact@company.com">
                </div>
                
                <div class="col-md-6 mb-3">
                    <label>Số điện thoại</label>
                    <input type="text" name="phone" class="form-control" 
                           value="{{ old('phone', $company->phone ?? '') }}">
                </div>
                
                <div class="col-md-6 mb-3">
                    <label>Ngày công chuẩn (để tính lương)</label>
                    <input type="number" name="standard_working_days" class="form-control" 
                           value="{{ old('standard_working_days', $company->standard_working_days ?? 26) }}">
                    @if(!$isEdit)
                        <small class="text-muted">Thường là 24 hoặc 26 ngày/tháng</small>
                    @endif
                </div>
                
                <div class="col-12 mb-3">
                    <label>Địa chỉ</label>
                    <textarea name="address" class="form-control" rows="2">{{ old('address', $company->address ?? '') }}</textarea>
                </div>
            </div>

            <button class="btn {{ $isEdit ? 'btn-primary' : 'btn-success' }}">
                {{ $isEdit ? 'Cập nhật' : 'Lưu lại' }}
            </button>
            <a href="{{ route('companies.index') }}" class="btn btn-secondary">Hủy</a>
        </form>
    </div>
</div>
@endsection