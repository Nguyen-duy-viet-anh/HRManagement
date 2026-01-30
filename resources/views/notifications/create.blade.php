@extends('layout')

@section('content')
<div class="container py-4">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-megaphone-fill me-2"></i>Soạn Thông Báo Hệ Thống</h5>
        </div>
        <div class="card-body">
            
            {{-- Form gửi dữ liệu sang hàm send --}}
            <form action="{{ route('notifications.send') }}" method="POST">
                @csrf
                
                <div class="mb-3">
                    <label class="fw-bold form-label">Tiêu đề thông báo:</label>
                    <input type="text" name="title" class="form-control" required placeholder="Ví dụ: Thông báo nghỉ lễ 30/4...">
                </div>

                <div class="mb-3">
                    <label class="fw-bold form-label">Nội dung chi tiết:</label>
                    <textarea name="content" class="form-control" rows="6" required placeholder="Nhập nội dung thông báo..."></textarea>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="{{ route('dashboard') }}" class="btn btn-secondary">Quay lại</a>
                    <button type="submit" class="btn btn-success px-4 fw-bold">
                        <i class="bi bi-send-fill me-2"></i>Gửi Thông Báo
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection