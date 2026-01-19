@extends('layout')

@section('content')

<div class="row justify-content-center">
    <div class="col-md-4">
        <div class="card shadow">
            <div class="card-header text-center bg-white">
                <h4>ĐĂNG NHẬP</h4>
            </div>
            <div class="card-body">
                <form action="{{ route('login.post') }}" method="POST">
                    
                    @csrf
                    
                    <div class="mb-3">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" required 
                               placeholder="admin@gmail.com" value="admin@gmail.com">
                    </div>
                    <div class="mb-3">
                        <label>Mật khẩu</label>
                        <input type="password" name="password" class="form-control" required 
                               placeholder="123456" value="123456">
                    </div>
                    <button class="btn btn-primary w-100">Đăng nhập</button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection