@extends('layout')

@section('content')
<div class="container py-4">
    {{-- Form chính bao quanh tất cả để submit cùng lúc --}}
    <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="row">
            {{-- CỘT TRÁI: AVATAR & THÔNG TIN CÔNG VIỆC & FILE ĐÍNH KÈM --}}
            <div class="col-md-4">
                {{-- 1. Card Avatar --}}
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
                            <i class="bi bi-camera me-1"></i> Thay đổi ảnh đại diện
                        </label>
                        <input type="file" name="avatar" id="avatar" class="d-none" onchange="previewImage(this)">
                    </div>
                </div>

                {{-- 2. Card Thông tin công việc --}}
                <div class="card shadow border-0 mb-4">
                    <div class="card-header bg-light fw-bold small text-uppercase">
                        <i class="bi bi-briefcase-fill me-2 text-secondary"></i>Thông tin công việc
                    </div>
                    <div class="card-body">
                        <div class="mb-2 border-bottom pb-2">
                            <label class="small text-muted d-block">Công ty:</label>
                            <span class="fw-bold text-primary">{{ $user->company->name ?? 'N/A' }}</span>
                        </div>
                        <div class="mb-2 border-bottom pb-2">
                            <label class="small text-muted d-block">Ngày vào làm:</label>
                            <span class="fw-bold">{{ $user->start_date ? \Carbon\Carbon::parse($user->start_date)->format('d/m/Y') : 'Chưa cập nhật' }}</span>
                        </div>
                        <div class="mb-0">
                            <label class="small text-muted d-block">Lương cơ bản:</label>
                            <span class="fw-bold text-success">{{ number_format($user->base_salary) }} VNĐ</span>
                        </div>
                    </div>
                </div>

                {{-- 3. [MỚI] Card Hồ sơ đính kèm (CCCD, Bằng cấp...) --}}
                {{-- 3. Card Danh sách Hồ sơ đính kèm --}}
<div class="card shadow border-0">
    <div class="card-header bg-light fw-bold small text-uppercase d-flex justify-content-between align-items-center">
        <span><i class="bi bi-folder-fill me-2 text-warning"></i>Hồ sơ đã lưu</span>
        <span class="badge bg-secondary">{{ $user->files->count() }} file</span>
    </div>
    <div class="card-body p-0">
        @if($user->files && $user->files->count() > 0)
            <div class="list-group list-group-flush">
                {{-- CHỈ HIỂN THỊ 5 FILE MỚI NHẤT --}}
                @foreach($user->files->sortByDesc('created_at')->take(5) as $file)
                    <div class="list-group-item d-flex justify-content-between align-items-center px-3">
                        <a href="{{ asset('storage/' . $file->file_path) }}" target="_blank" class="text-decoration-none text-dark small text-truncate d-flex align-items-center" style="max-width: 85%;">
                            @if(\Illuminate\Support\Str::endsWith(strtolower($file->file_path), ['.jpg', '.png', '.jpeg']))
                                <i class="bi bi-image text-success me-2"></i>
                            @else
                                <i class="bi bi-file-earmark-text text-primary me-2"></i>
                            @endif
                            {{ $file->original_name }}
                        </a>
                        {{-- Nút xóa nhỏ --}}
                        <button type="button" class="btn btn-sm text-danger p-0 border-0" onclick="deleteFile('{{ $file->id }}')">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                @endforeach
            </div>
            
            {{-- NÚT XEM TOÀN BỘ (NẾU CÓ NHIỀU HƠN 0 FILE) --}}
            <div class="card-footer bg-white text-center p-2">
                <a href="{{ route('profile.files') }}" class="btn btn-sm btn-outline-primary w-100 fw-bold">
                    <i class="bi bi-grid-3x3-gap me-1"></i> XEM TOÀN BỘ ALBUM
                </a>
            </div>
        @else
            <div class="p-4 text-center text-muted small fst-italic">
                Chưa có tài liệu nào.
            </div>
        @endif
    </div>
</div>
            </div>

            {{-- CỘT PHẢI: FORM CHỈNH SỬA --}}
            <div class="col-md-8">
                <div class="card shadow border-0">
                    <div class="card-header bg-white py-3">
                        <h5 class="m-0 fw-bold text-primary">
                            <i class="bi bi-person-gear me-2"></i>CHỈNH SỬA HỒ SƠ CÁ NHÂN
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        {{-- Thông báo lỗi --}}
                        @if ($errors->any())
                            <div class="alert alert-danger mb-4">
                                <ul class="mb-0 small">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="fw-bold form-label">Họ và tên</label>
                                <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="fw-bold form-label">Email đăng nhập</label>
                                <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="fw-bold form-label">Số điện thoại</label>
                                <input type="text" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="fw-bold form-label">Giới tính</label>
                                <select name="gender" class="form-select">
                                    <option value="male" {{ $user->gender == 'male' ? 'selected' : '' }}>Nam</option>
                                    <option value="female" {{ $user->gender == 'female' ? 'selected' : '' }}>Nữ</option>
                                    <option value="other" {{ $user->gender == 'other' ? 'selected' : '' }}>Khác</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="fw-bold form-label">Ngày sinh</label>
                                <input type="date" name="birthday" class="form-control" value="{{ old('birthday', $user->birthday) }}">
                            </div>
                            <div class="col-12">
                                <label class="fw-bold form-label">Địa chỉ cư trú</label>
                                <textarea name="address" class="form-control" rows="2">{{ old('address', $user->address) }}</textarea>
                            </div>

                            {{-- [MỚI] Ô UPLOAD NHIỀU FILE --}}
                            <div class="col-12 mt-4">
                                <div class="bg-light p-3 rounded border border-dashed">
                                    <label class="fw-bold form-label text-primary">
                                        <i class="bi bi-cloud-upload me-2"></i>Tải lên tài liệu (CCCD, Bằng cấp...)
                                    </label>
                                    <input type="file" name="documents[]" class="form-control" multiple>
                                    <small class="text-muted d-block mt-1">
                                        <i class="bi bi-info-circle me-1"></i>
                                        Giữ phím <strong>Ctrl</strong> (hoặc Command) để chọn nhiều file cùng lúc.
                                    </small>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">
                        
                        <h6 class="text-muted text-uppercase fw-bold small mb-3">Đổi mật khẩu</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="fw-bold form-label">Mật khẩu mới</label>
                                <input type="password" name="password" class="form-control" placeholder="Để trống nếu không đổi">
                            </div>
                            <div class="col-md-6">
                                <label class="fw-bold form-label">Xác nhận mật khẩu</label>
                                <input type="password" name="password_confirmation" class="form-control" placeholder="Nhập lại mật khẩu mới">
                            </div>
                        </div>

                        <div class="text-end mt-4">
                            <button type="submit" class="btn btn-primary px-5 fw-bold shadow-sm">
                                <i class="bi bi-check-lg me-1"></i> LƯU THAY ĐỔI
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

{{-- SCRIPT XỬ LÝ ẢNH PREVIEW & XÓA FILE --}}
<script>
    // Preview Avatar
    function previewImage(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('preview').src = e.target.result;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    // Hàm xóa file (Tạo form ảo để gửi request DELETE)
    function deleteFile(fileId) {
        if(confirm('Bạn có chắc chắn muốn xóa tài liệu này không?')) {
            let form = document.createElement('form');
            form.method = 'POST';
            form.action = '/user-files/' + fileId; // Route đã định nghĩa trong web.php
            
            // CSRF Token
            let csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';
            form.appendChild(csrfToken);
            
            // Method DELETE
            let methodField = document.createElement('input');
            methodField.type = 'hidden';
            methodField.name = '_method';
            methodField.value = 'DELETE';
            form.appendChild(methodField);
            
            document.body.appendChild(form);
            form.submit();
        }
    }
</script>
@endsection