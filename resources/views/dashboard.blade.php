@extends('layout')

@section('content')

{{-- 1. PHẦN HEADER CHÀO MỪNG --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold text-dark mb-1">
            <i class="bi bi-emoji-smile-fill text-warning me-2"></i>Xin chào, {{ Auth::user()->name }}
        </h3>
        <p class="text-muted mb-0">Chúc bạn một ngày làm việc năng suất!</p>
    </div>
    <div class="d-none d-md-block">
        <div class="bg-white px-3 py-2 rounded-pill shadow-sm border d-flex align-items-center text-secondary">
            <i class="bi bi-calendar-event me-2 text-primary"></i>
            <span class="fw-bold">{{ date('d/m/Y') }}</span>
        </div>
    </div>
</div>

{{-- 2. THÔNG BÁO (ALERT) --}}
@if(session('success'))
    <div class="alert alert-success border-0 shadow-sm d-flex align-items-center mb-4">
        <i class="bi bi-check-circle-fill me-2 fs-5"></i>
        <div>{{ session('success') }}</div>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center mb-4">
        <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
        <div>{{ session('error') }}</div>
    </div>
@endif

{{-- 3. CARD THÔNG TIN CÁ NHÂN (Hiển thị cho tất cả nếu đã có công ty) --}}
@if(Auth::user()->company_id)
<div class="card border-0 shadow-sm mb-4 overflow-hidden">
    <div class="card-body p-0">
        <div class="row g-0">
            <div class="col-md-4 border-end border-light p-4">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-primary bg-opacity-10 p-3 me-3 text-primary">
                        <i class="bi bi-building fs-4"></i>
                    </div>
                    <div>
                        <small class="text-uppercase text-muted fw-bold" style="font-size: 0.7rem;">Đơn vị công tác</small>
                        <h6 class="fw-bold text-dark mb-0 mt-1 text-truncate">{{ Auth::user()->company->name ?? 'Chưa cập nhật' }}</h6>
                    </div>
                </div>
            </div>

            <div class="col-md-4 border-end border-light p-4">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-warning bg-opacity-10 p-3 me-3 text-warning">
                        <i class="bi bi-calendar-check fs-4"></i>
                    </div>
                    <div>
                        <small class="text-uppercase text-muted fw-bold" style="font-size: 0.7rem;">Công tháng {{ date('m/Y') }}</small>
                        <h6 class="fw-bold text-dark mb-0 mt-1">{{ $my_work_days }} ngày</h6>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 p-4">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-success bg-opacity-10 p-3 me-3 text-success">
                        <i class="bi bi-cash-stack fs-4"></i>
                    </div>
                    <div>
                        <small class="text-uppercase text-muted fw-bold" style="font-size: 0.7rem;">Lương cơ bản</small>
                        <h6 class="fw-bold text-success mb-0 mt-1">{{ number_format(Auth::user()->base_salary) }} VNĐ</h6>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

{{-- 4. KHU VỰC QUẢN TRỊ (ADMIN & MANAGER) --}}
@if(Auth::user()->role == 0 || Auth::user()->role == 1)
<h6 class="text-muted fw-bold text-uppercase mb-3 small ls-1">Tổng quan hệ thống</h6>
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center justify-content-between p-4">
                <div>
                    <h6 class="text-muted small text-uppercase fw-bold mb-1">Tổng Công Ty</h6>
                    <h2 class="mb-0 fw-bold text-dark">{{ $total_companies }}</h2>
                </div>
                <div class="text-primary opacity-25">
                    <i class="bi bi-buildings-fill" style="font-size: 3rem;"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center justify-content-between p-4">
                <div>
                    <h6 class="text-muted small text-uppercase fw-bold mb-1">Tổng Nhân Sự</h6>
                    <h2 class="mb-0 fw-bold text-dark">{{ $total_users }}</h2>
                </div>
                <div class="text-success opacity-25">
                    <i class="bi bi-people-fill" style="font-size: 3rem;"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center justify-content-between p-4">
                <div>
                    <h6 class="text-muted small text-uppercase fw-bold mb-1">Đi làm hôm nay</h6>
                    <h2 class="mb-0 fw-bold text-info">{{ $present_today }}</h2>
                </div>
                <div class="text-info opacity-25">
                    <i class="bi bi-person-check-fill" style="font-size: 3rem;"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3">
                <h6 class="m-0 fw-bold text-dark"><i class="bi bi-grid-fill me-2 text-secondary"></i>Truy cập nhanh</h6>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-6">
                        <a href="{{ route('attendance.index') }}" class="btn btn-light w-100 text-start py-3 border h-100 d-flex flex-column justify-content-center">
                            <i class="bi bi-fingerprint fs-3 text-primary mb-2"></i>
                            <span class="fw-bold small text-dark">Chấm công</span>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="{{ route('users.create') }}" class="btn btn-light w-100 text-start py-3 border h-100 d-flex flex-column justify-content-center">
                            <i class="bi bi-person-plus-fill fs-3 text-success mb-2"></i>
                            <span class="fw-bold small text-dark">Thêm nhân viên</span>
                        </a>
                    </div>
                    <div class="col-12">
                        <a href="{{ route('salary.index') }}" class="btn btn-light w-100 text-start py-3 border d-flex align-items-center">
                            <i class="bi bi-currency-dollar fs-4 text-warning me-3"></i>
                            <span class="fw-bold small text-dark">Xem bảng lương tháng này</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 fw-bold text-dark"><i class="bi bi-bell-fill me-2 text-danger"></i>Thông báo</h6>
                <span class="badge bg-danger bg-opacity-10 text-danger">Mới</span>
            </div>
            <div class="card-body">
                <div class="d-flex border-bottom pb-3 mb-3">
                    <div class="me-3">
                        <div class="bg-primary bg-opacity-10 text-primary rounded p-2">
                            <i class="bi bi-info-circle-fill"></i>
                        </div>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1">Hệ thống hoạt động bình thường</h6>
                        <p class="text-muted small mb-0">Tất cả các dịch vụ database và cache đang vận hành ổn định.</p>
                        <small class="text-muted" style="font-size: 0.7rem;">Vừa cập nhật</small>
                    </div>
                </div>
                <div class="text-center mt-auto">
                    <small class="text-muted fst-italic">Phiên bản 1.0 - HRM System</small>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

{{-- 5. KHU VỰC NHÂN VIÊN (CHẤM CÔNG) --}}
@if(Auth::user()->role == 2)
<div class="row justify-content-center mt-4">
    <div class="col-md-6 col-lg-5">
        
        @if($todayAttendance && $todayAttendance->status == 1)
            {{-- TRẠNG THÁI: ĐÃ CHẤM CÔNG --}}
            <div class="card shadow border-0 text-center overflow-hidden">
                <div class="card-body py-5 bg-success bg-opacity-10">
                    <div class="mb-3">
                        <div class="d-inline-flex align-items-center justify-content-center bg-white rounded-circle shadow-sm" style="width: 80px; height: 80px;">
                            <i class="bi bi-check-lg display-4 text-success"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold text-success">Đã hoàn thành!</h3>
                    <p class="text-muted mb-4">
                        Bạn đã điểm danh lúc 
                        <span class="fw-bold text-dark">
                            {{ $todayAttendance->created_at->setTimezone('Asia/Ho_Chi_Minh')->format('H:i') }}
                        </span>
                    </p>
                    
                    <button class="btn btn-success px-5 rounded-pill fw-bold shadow-sm" disabled>
                        <i class="bi bi-shield-check me-2"></i>Đã ghi nhận
                    </button>
                </div>
                <div class="card-footer bg-success text-white border-0 py-2 small">
                    Chúc bạn làm việc vui vẻ!
                </div>
            </div>

        @else
            {{-- TRẠNG THÁI: CHƯA CHẤM CÔNG --}}
            <div class="card shadow border-0 text-center">
                <div class="card-header bg-white border-0 pt-4 pb-0">
                    <h5 class="fw-bold text-uppercase ls-1 text-primary">Điểm danh đi làm</h5>
                </div>
                <div class="card-body py-4">
                    <div class="mb-4">
                        <p class="text-muted mb-2">Hôm nay: <span class="fw-bold text-dark">{{ date('d/m/Y') }}</span></p>
                        @if($todayAttendance && $todayAttendance->status == 0)
                            <div class="badge bg-danger bg-opacity-10 text-danger px-3 py-2 rounded-pill">
                                <i class="bi bi-exclamation-circle me-1"></i> Đang ghi nhận vắng
                            </div>
                        @endif
                    </div>
                    
                    <form action="{{ route('attendance.self') }}" method="POST">
                        @csrf
                        {{-- Nút bấm chấm công lớn, nổi bật --}}
                        <button type="submit" class="btn btn-primary btn-lg rounded-circle shadow-lg pulse-animation" style="width: 120px; height: 120px; border: 4px solid #e1e9ff;">
                            <div class="d-flex flex-column align-items-center justify-content-center h-100">
                                <i class="bi bi-fingerprint fs-1 mb-1"></i>
                                <span class="small fw-bold text-uppercase" style="font-size: 0.7rem;">Bấm ngay</span>
                            </div>
                        </button>
                    </form>
                    <p class="mt-4 text-muted small">Nhấn vào nút tròn để xác nhận có mặt</p>
                </div>
            </div>
        @endif

    </div>
</div>

<style>
    /* Hiệu ứng nhịp đập cho nút chấm công */
    .pulse-animation {
        animation: pulse 2s infinite;
    }
    @keyframes pulse {
        0% { box-shadow: 0 0 0 0 rgba(13, 110, 253, 0.4); }
        70% { box-shadow: 0 0 0 15px rgba(13, 110, 253, 0); }
        100% { box-shadow: 0 0 0 0 rgba(13, 110, 253, 0); }
    }
    .ls-1 { letter-spacing: 1px; }
</style>
@endif

@endsection