@extends('layout')

@section('content')
<div class="container py-4">
    <div class="card shadow border-0">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold text-primary">
                @if(isset($user))
                    <i class="bi bi-pencil-square me-2"></i>Sửa hồ sơ: {{ $user->name }}
                @else
                    <i class="bi bi-person-plus-fill me-2"></i>Thêm nhân viên mới
                @endif
            </h5>
            <a href="{{ route('users.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Quay lại danh sách
            </a>
        </div>
        
        <div class="card-body p-4">
            {{-- Hiển thị lỗi validate nếu có --}}
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0 small">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- FORM BẮT ĐẦU --}}
            <form action="{{ isset($user) ? route('users.update', $user->id) : route('users.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @if(isset($user))
                    @method('PUT')
                @endif

                <div class="row g-3">
                    {{-- 1. CỘT TRÁI: THÔNG TIN ĐĂNG NHẬP --}}
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Họ và tên <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $user->name ?? '') }}" required placeholder="Ví dụ: Nguyễn Văn A">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Email (Tên đăng nhập) <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" value="{{ old('email', $user->email ?? '') }}" required placeholder="email@example.com">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Mật khẩu {{ isset($user) ? '(Bỏ trống nếu không đổi)' : '*' }}</label>
                        <input type="password" name="password" class="form-control" {{ isset($user) ? '' : 'required' }}>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Lương cơ bản (VNĐ) <span class="text-danger">*</span></label>
                        <input type="number" name="base_salary" class="form-control" value="{{ old('base_salary', $user->base_salary ?? 0) }}" required>
                    </div>

                    {{-- Chọn Công ty (Chỉ hiện nếu là Admin tổng) --}}
                    @if(Auth::user()->role == 0)
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Thuộc công ty</label>
                        <select name="company_id" class="form-select">
                            <option value="">-- Không chọn (Admin hệ thống) --</option>
                            @foreach($companies as $c)
                                <option value="{{ $c->id }}" {{ (old('company_id', $user->company_id ?? '') == $c->id) ? 'selected' : '' }}>
                                    {{ $c->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Phân quyền</label>
                        <select name="role" class="form-select">
                            <option value="2" {{ (old('role', $user->role ?? 2) == 2) ? 'selected' : '' }}>Nhân viên</option>
                            <option value="1" {{ (old('role', $user->role ?? 2) == 1) ? 'selected' : '' }}>Quản lý công ty</option>
                            @if(Auth::user()->role == 0)
                                <option value="0" {{ (old('role', $user->role ?? 2) == 0) ? 'selected' : '' }}>Admin Tổng</option>
                            @endif
                        </select>
                    </div>

                    <div class="col-12 mt-3">
                        <label class="form-label fw-bold">Ảnh đại diện</label>
                        <div class="d-flex align-items-center gap-3">
                            @if(isset($user) && $user->avatar)
                                <img src="{{ $user->avatar_url }}" class="rounded-circle border shadow-sm" width="60" height="60" style="object-fit: cover;">
                            @endif
                            <input type="file" name="avatar" class="form-control">
                        </div>
                    </div>

                    <hr class="my-4 text-muted">

                    {{-- 2. PHẦN QUẢN LÝ TÀI LIỆU (ĐIỂM NHẤN CỦA BẠN) --}}
                    <div class="col-12">
                        <h6 class="fw-bold text-primary mb-3"><i class="bi bi-folder2-open me-2"></i>Hồ sơ đính kèm (CCCD, Bằng cấp, Hợp đồng)</h6>
                        
                        {{-- Ô upload nhiều file --}}
                        <div class="bg-light p-4 rounded border border-dashed mb-4 text-center">
                            <label for="fileUpload" class="form-label fw-bold mb-2 cursor-pointer text-primary">
                                <i class="bi bi-cloud-arrow-up display-6"></i><br>
                                Click để chọn tài liệu tải lên
                            </label>
                            <input type="file" id="fileUpload" name="documents[]" class="form-control" multiple>
                            <small class="text-muted d-block mt-2">
                                (Giữ phím <strong>Ctrl</strong> để chọn nhiều file cùng lúc. Hỗ trợ ảnh, PDF, Doc...)
                            </small>
                        </div>

                        {{-- DANH SÁCH FILE ĐÃ CÓ (Chỉ hiện khi Sửa nhân viên) --}}
                        @if(isset($user) && $user->files->count() > 0)
                            <div class="d-flex justify-content-between align-items-end mb-3">
                                <div>
                                    <label class="fw-bold text-dark mb-0">Tài liệu hiện có ({{ $user->files->count() }}):</label>
                                    <div class="small text-muted">Hiển thị 4 tài liệu mới nhất</div>
                                </div>
                                
                                {{-- [QUAN TRỌNG] NÚT XEM TOÀN BỘ ALBUM --}}
                                <a href="{{ route('users.files', $user->id) }}" class="btn btn-sm btn-primary fw-bold shadow-sm">
                                    <i class="bi bi-grid-3x3-gap-fill me-1"></i> Quản lý toàn bộ Album
                                </a>
                            </div>

                            {{-- Hiển thị rút gọn (4 file mới nhất) --}}
                            <div class="row g-2">
                                @foreach($user->files->sortByDesc('created_at')->take(4) as $file)
                                    <div class="col-md-3 col-6">
                                        <div class="d-flex align-items-center p-2 border rounded bg-white shadow-sm position-relative h-100">
                                            {{-- Icon phân loại file --}}
                                            <div class="me-2 fs-4">
                                                @if(\Illuminate\Support\Str::endsWith(strtolower($file->file_path), ['.jpg', '.png', '.jpeg']))
                                                    <i class="bi bi-image text-success"></i>
                                                @elseif(\Illuminate\Support\Str::endsWith(strtolower($file->file_path), ['.pdf']))
                                                    <i class="bi bi-file-pdf text-danger"></i>
                                                @else
                                                    <i class="bi bi-file-earmark-text text-secondary"></i>
                                                @endif
                                            </div>
                                            
                                            {{-- Tên file (Click để xem) --}}
                                            <div class="text-truncate small fw-bold" style="flex: 1;">
                                                <a href="{{ asset('storage/' . $file->file_path) }}" target="_blank" class="text-decoration-none text-dark" title="{{ $file->original_name }}">
                                                    {{ $file->original_name }}
                                                </a>
                                            </div>

                                            {{-- Nút xóa nhanh --}}
                                            <button type="button" class="btn btn-link text-danger p-0 ms-2" onclick="deleteFile('{{ $file->id }}')" title="Xóa file này">
                                                <i class="bi bi-x-circle-fill"></i>
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            {{-- Thông báo nếu còn nhiều file ẩn --}}
                            @if($user->files->count() > 4)
                                <div class="text-center mt-2 small text-muted fst-italic">
                                    ... còn {{ $user->files->count() - 4 }} tài liệu khác. Hãy bấm nút "Quản lý toàn bộ Album" để xem.
                                </div>
                            @endif
                        @endif
                    </div>
                </div>

                {{-- NÚT LƯU --}}
                <div class="mt-5 text-end border-top pt-3">
                    <button type="submit" class="btn btn-success px-5 fw-bold shadow-lg">
                        <i class="bi bi-check-circle-fill me-2"></i>LƯU THÔNG TIN
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- SCRIPT XÓA FILE NHANH --}}
<script>
    function deleteFile(fileId) {
        if(confirm('CẢNH BÁO: Bạn có chắc chắn muốn xóa vĩnh viễn file này không?')) {
            let form = document.createElement('form');
            form.method = 'POST';
            form.action = '/user-files/' + fileId; 
            
            let csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';
            form.appendChild(csrfToken);
            
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