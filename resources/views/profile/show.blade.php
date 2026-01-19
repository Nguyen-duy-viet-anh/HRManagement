@extends('layout')

@section('content')
<div class="container py-4">
    <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="row">
            <div class="col-md-4">
                <div class="card shadow border-0 mb-4">
                    <div class="card-body text-center">
                        @php
                            $avatar = Auth::user()->avatar;
                            $url = filter_var($avatar, FILTER_VALIDATE_URL) 
                                   ? $avatar 
                                   : ($avatar ? asset('storage/' . $avatar) : 'https://ui-avatars.com/api/?name=' . urlencode(Auth::user()->name));
                        @endphp
                        <img src="{{ $url }}" id="preview" class="rounded-circle img-thumbnail mb-3 shadow-sm" style="width: 150px; height: 150px; object-fit: cover;">
                        
                        <h5 class="fw-bold">{{ Auth::user()->name }}</h5>
                        <p class="text-muted small">{{ Auth::user()->email }}</p>
                        
                        <label for="avatar" class="btn btn-sm btn-outline-primary w-100">
                            <i class="bi bi-camera me-1"></i> Thay đổi ảnh
                        </label>
                        <input type="file" name="avatar" id="avatar" class="d-none" onchange="previewImage(this)">
                    </div>
                </div>

                <div class="card shadow border-0">
                    <div class="card-header bg-light fw-bold">THÔNG TIN CÔNG VIỆC</div>
                    <div class="card-body">
                        <div class="mb-2">
                            <label class="small text-muted d-block">Công ty:</label>
                            <span class="fw-bold">{{ $user->company->name ?? 'N/A' }}</span>
                        </div>
                        <div class="mb-2">
                            <label class="small text-muted d-block">Ngày vào làm:</label>
                            <span class="fw-bold">{{ $user->start_date ?? 'Chưa cập nhật' }}</span>
                        </div>
                        <div class="mb-2">
                            <label class="small text-muted d-block">Lương cơ bản:</label>
                            <span class="fw-bold text-success">{{ number_format($user->base_salary) }} VNĐ</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card shadow border-0">
                    <div class="card-header bg-white py-3">
                        <h5 class="m-0 fw-bold text-primary">CHỈNH SỬA HỒ SƠ CÁ NHÂN</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="fw-bold">Họ và tên</label>
                                <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="fw-bold">Email</label>
                                <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="fw-bold">Số điện thoại</label>
                                <input type="text" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="fw-bold">Giới tính</label>
                                <select name="gender" class="form-select">
                                    <option value="male" {{ $user->gender == 'male' ? 'selected' : '' }}>Nam</option>
                                    <option value="female" {{ $user->gender == 'female' ? 'selected' : '' }}>Nữ</option>
                                    <option value="other" {{ $user->gender == 'other' ? 'selected' : '' }}>Khác</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="fw-bold">Ngày sinh</label>
                                <input type="date" name="birthday" class="form-control" value="{{ old('birthday', $user->birthday) }}">
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="fw-bold">Địa chỉ cư trú</label>
                                <textarea name="address" class="form-control" rows="2">{{ old('address', $user->address) }}</textarea>
                            </div>
                        </div>

                        <hr>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="fw-bold">Mật khẩu mới</label>
                                <input type="password" name="password" class="form-control" placeholder="Để trống nếu không đổi">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="fw-bold">Xác nhận mật khẩu</label>
                                <input type="password" name="password_confirmation" class="form-control" placeholder="Nhập lại mật khẩu mới">
                            </div>
                        </div>

                        <div class="text-end mt-3">
                            <button type="submit" class="btn btn-primary px-5 fw-bold">LƯU THAY ĐỔI</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    function previewImage(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('preview').src = e.target.result;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>
@endsection