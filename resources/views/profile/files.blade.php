@extends('layout')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold text-primary">
            <i class="bi bi-images me-2"></i>Thư viện tài liệu của tôi
        </h4>
        <a href="{{ route('profile.show') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Quay lại hồ sơ
        </a>
    </div>

    @if($files->count() > 0)
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
            @foreach($files as $file)
                <div class="col">
                    <div class="card h-100 shadow-sm border-0">
                        {{-- PHẦN HIỂN THỊ ẢNH/ICON --}}
                        <div class="card-img-top bg-light d-flex align-items-center justify-content-center overflow-hidden position-relative" style="height: 200px;">
                            <a href="{{ asset('storage/' . $file->file_path) }}" target="_blank" class="w-100 h-100 d-flex align-items-center justify-content-center text-decoration-none">
                                @if(\Illuminate\Support\Str::endsWith(strtolower($file->file_path), ['.jpg', '.jpeg', '.png', '.gif', '.webp']))
                                    {{-- Nếu là ảnh: Hiện ảnh thật --}}
                                    <img src="{{ asset('storage/' . $file->file_path) }}" class="w-100 h-100" style="object-fit: cover; transition: 0.3s;">
                                @elseif(\Illuminate\Support\Str::endsWith(strtolower($file->file_path), ['.pdf']))
                                    {{-- Nếu là PDF --}}
                                    <div class="text-center text-danger">
                                        <i class="bi bi-file-earmark-pdf-fill" style="font-size: 4rem;"></i>
                                        <div class="small fw-bold mt-2">Tài liệu PDF</div>
                                    </div>
                                @else
                                    {{-- File khác --}}
                                    <div class="text-center text-primary">
                                        <i class="bi bi-file-earmark-text-fill" style="font-size: 4rem;"></i>
                                        <div class="small fw-bold mt-2">Tài liệu</div>
                                    </div>
                                @endif
                            </a>
                        </div>

                        {{-- PHẦN THÔNG TIN FILE --}}
                        <div class="card-body p-3">
                            <h6 class="card-title text-truncate" title="{{ $file->original_name }}">
                                {{ $file->original_name }}
                            </h6>
                            <p class="card-text small text-muted mb-2">
                                <i class="bi bi-clock me-1"></i> {{ $file->created_at->format('d/m/Y H:i') }}
                            </p>
                        </div>

                        {{-- NÚT XÓA --}}
                        <div class="card-footer bg-white border-top-0 d-flex justify-content-between">
                            {{-- <a href="{{ asset('storage/' . $file->file_path) }}" target="_blank" class="btn btn-sm btn-outline-primary" title="Xem/Tải xuống">
                                <i class="bi bi-download"></i>
                            </a> --}}
                            
                            <form action="{{ route('user_files.destroy', $file->id) }}" method="POST" onsubmit="return confirm('Bạn chắc chắn muốn xóa file này?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Xóa file">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- PHÂN TRANG --}}
        <div class="mt-4 d-flex justify-content-center">
            {{ $files->links() }}
        </div>
    @else
        <div class="text-center py-5 bg-light rounded">
            <i class="bi bi-cloud-slash display-4 text-muted"></i>
            <p class="mt-3 text-muted">Bạn chưa tải lên tài liệu hoặc hình ảnh nào.</p>
            <a href="{{ route('profile.show') }}" class="btn btn-primary mt-2">Tải lên ngay</a>
        </div>
    @endif
</div>
@endsection