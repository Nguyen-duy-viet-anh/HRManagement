@extends('layout')

@section('content')
<style>
    body { background-color: #f0f2f5; } 
</style>

<div class="container d-flex flex-column justify-content-center" style="min-height: 80vh;">
    <div class="row justify-content-center">
        <div class="col-md-4">
            
            <div class="text-center mb-4">
                <h3 class="fw-bold text-dark">HR SYSTEM</h3>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body p-4">

                    @if(session('error'))
                        <div class="alert alert-danger text-center small mb-3">
                            {{ session('error') }}
                        </div>
                    @endif

                    <form action="{{ route('login.post') }}" method="POST">
                        @csrf
                        
                        <div class="mb-3">
                            <label class="fw-bold small text-muted mb-1">EMAIL</label>
                            <input type="email" name="email" class="form-control py-2" 
                                   value="admin@gmail.com" placeholder="Nhập email..." required>
                        </div>

                        <div class="mb-4">
                            <label class="fw-bold small text-muted mb-1">MẬT KHẨU</label>
                            <input type="password" name="password" class="form-control py-2" 
                                   value="123456" placeholder="Nhập mật khẩu..." required>
                        </div>

                        <button class="btn btn-primary w-100 py-2 fw-bold">
                            ĐĂNG NHẬP
                        </button>
                    </form>

                </div>
            </div>

            <div class="text-center mt-3">
                <small class="text-muted">Phiên bản 1.0 &copy; 2026</small>
            </div>

        </div>
    </div>
</div>

@endsection