@extends('layout')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="m-0">👤 HỒ SƠ CÁ NHÂN</h5>
                    <span class="badge bg-warning text-dark">Nhân viên</span>
                </div>
                
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('profile.update') }}" method="POST">
                        @csrf
                        
                        <div class="mb-3">
                            <label class="fw-bold text-muted">Công ty làm việc:</label>
                            <input type="text" class="form-control bg-light" 
                                   value="{{ $user->company ? $user->company->name : 'Chưa có công ty' }}" 
                                   disabled readonly>
                            <small class="text-muted fst-italic">* Bạn không thể tự thay đổi công ty.</small>
                        </div>

                        <hr>

                        <div class="mb-3">
                            <label class="fw-bold">Họ và Tên:</label>
                            <input type="text" name="name" class="form-control" 
                                   value="{{ old('name', $user->name) }}" required>
                        </div>

                        <div class="mb-3">
                            <label class="fw-bold">Email đăng nhập:</label>
                            <input type="email" name="email" class="form-control" 
                                   value="{{ old('email', $user->email) }}" required>
                        </div>

                        <div class="mb-3">
                            <label class="fw-bold">Đổi mật khẩu mới:</label>
                            <input type="password" name="password" class="form-control" 
                                   placeholder="Bỏ trống nếu không muốn đổi mật khẩu">
                        </div>

                        <div class="d-grid gap-2 col-6 mx-auto mt-4">
                            <button type="submit" class="btn btn-success fw-bold">
                                💾 Cập nhật hồ sơ
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection