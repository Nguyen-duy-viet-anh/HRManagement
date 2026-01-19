@extends('layout')

@section('content')
<div class="card shadow">
    <div class="card-header">
        <h5 class="m-0 text-primary">Chỉnh sửa công ty: {{ $company->name }}</h5>
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

        <form action="{{ route('companies.update', $company->id) }}" method="POST">
            @csrf
            @method('PUT') <div class="row">
                <div class="col-md-6 mb-3">
                    <label>Tên công ty <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="{{ $company->name }}" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" value="{{ $company->email }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label>Số điện thoại</label>
                    <input type="text" name="phone" class="form-control" value="{{ $company->phone }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label>Ngày công chuẩn</label>
                    <input type="number" name="standard_working_days" class="form-control" value="{{ $company->standard_working_days }}">
                </div>
                <div class="col-12 mb-3">
                    <label>Địa chỉ</label>
                    <textarea name="address" class="form-control" rows="2">{{ $company->address }}</textarea>
                </div>
            </div>

            <button class="btn btn-primary">Cập nhật</button>
            <a href="{{ route('companies.index') }}" class="btn btn-secondary">Hủy</a>
        </form>
    </div>
</div>
@endsection