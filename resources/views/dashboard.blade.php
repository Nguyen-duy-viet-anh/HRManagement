@extends('layout')

@section('content')

<div class="row mb-4">
    <div class="col-md-8">
        <h3 class="text-secondary fw-bold">Xin chào, {{ Auth::user()->name }}!</h3>
        <p class="text-muted mb-0">Chúc bạn một ngày làm việc hiệu quả.</p>
    </div>
    <div class="col-md-4 text-end">
        <span class="badge bg-light text-dark border p-2">
            Hôm nay: {{ date('d/m/Y') }}
        </span>
    </div>
</div>

@if(Auth::user()->company_id)
<div class="card shadow-sm border-start border-4 border-primary mb-4">
    <div class="card-body">
        <div class="row text-center">
            
            <div class="col-md-4 border-end">
                <small class="text-muted text-uppercase fw-bold">Đơn vị công tác</small>
                <h5 class="text-primary fw-bold mt-2 mb-0">
                    {{ Auth::user()->company->name ?? 'Chưa cập nhật' }}
                </h5>
                <small class="text-muted">{{ Auth::user()->company->address ?? '' }}</small>
            </div>

            <div class="col-md-4 border-end">
                <small class="text-muted text-uppercase fw-bold">Công tháng {{ date('m/Y') }}</small>
                <h5 class="text-dark fw-bold mt-2 mb-0">
                    {{ $my_work_days }} ngày
                </h5>
                <small class="text-muted">Số ngày đã đi làm</small>
            </div>
            
            <div class="col-md-4">
                <small class="text-muted text-uppercase fw-bold">Mức lương cơ bản</small>
                <h5 class="text-success fw-bold mt-2 mb-0">
                    {{ number_format(Auth::user()->base_salary) }} VNĐ
                </h5>
                <small class="text-muted">Lương cứng chưa bao gồm phụ cấp</small>
            </div>

        </div>
    </div>
</div>
@endif

@if(session('success'))
    <div class="alert alert-success fw-bold">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-warning fw-bold">{{ session('error') }}</div>
@endif

@if(Auth::user()->role == 0 || Auth::user()->role == 1)
<div class="row">
    <div class="col-md-3 mb-4">
        <div class="card border-0 shadow-sm h-100 bg-primary text-white">
            <div class="card-body">
                <div class="text-uppercase fw-bold small opacity-75">Tổng số Công Ty</div>
                <div class="h2 mb-0 fw-bold">{{ $total_companies }}</div>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-4">
        <div class="card border-0 shadow-sm h-100 bg-success text-white">
            <div class="card-body">
                <div class="text-uppercase fw-bold small opacity-75">Tổng số Nhân sự</div>
                <div class="h2 mb-0 fw-bold">{{ $total_users }}</div>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-4">
        <div class="card border-0 shadow-sm h-100 bg-info text-dark">
            <div class="card-body">
                <div class="text-uppercase fw-bold small opacity-75">Đi làm hôm nay</div>
                <div class="h2 mb-0 fw-bold">{{ $present_today }}</div>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-4">
        <div class="card border-0 shadow-sm h-100 bg-warning text-dark">
            <div class="card-body">
                <div class="text-uppercase fw-bold small opacity-75">Quỹ lương (Tạm tính)</div>
                <div class="h4 mb-0 fw-bold">{{ number_format($total_estimated_salary) }} đ</div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-2">
    <div class="col-lg-6 mb-4">
        <div class="card shadow border-0 h-100">
            <div class="card-header bg-white fw-bold border-bottom">PHÍM TẮT CHỨC NĂNG</div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('attendance.index') }}" class="btn btn-outline-primary text-start fw-bold">
                        Quản lý chấm công
                    </a>
                    <a href="{{ route('users.create') }}" class="btn btn-outline-success text-start fw-bold">
                        Thêm nhân viên mới
                    </a>
                    <a href="{{ route('salary.index') }}" class="btn btn-outline-dark text-start fw-bold">
                        Bảng lương tháng này
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6 mb-4">
        <div class="card shadow border-0 h-100">
            <div class="card-header bg-white fw-bold border-bottom text-danger">THÔNG BÁO</div>
            <div class="card-body">
                <p>Hệ thống hoạt động bình thường.</p>
                <p class="text-muted small">Phiên bản 1.0 - Dành cho Quản trị viên</p>
            </div>
        </div>
    </div>
</div>
@endif

@if(Auth::user()->role == 2)
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow border-0 text-center">
            <div class="card-header bg-dark text-white fw-bold py-3">
                TRẠNG THÁI LÀM VIỆC
            </div>
            <div class="card-body py-5">
                @if($todayAttendance)
                    <h2 class="text-success fw-bold mb-3">ĐÃ CHẤM CÔNG</h2>
                    <p class="text-muted mb-4">Giờ vào làm: {{ $todayAttendance->created_at->format('H:i:s') }}</p>
                    <button class="btn btn-secondary w-100 fw-bold py-3" disabled>
                        TRẠNG THÁI: ĐÃ HOÀN THÀNH
                    </button>
                @else
                    <h2 class="text-warning fw-bold mb-3">CHƯA ĐIỂM DANH</h2>
                    <p class="mb-4">Vui lòng xác nhận để bắt đầu tính công ngày hôm nay.</p>
                    
                    <form action="{{ route('attendance.self') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold py-3">
                            XÁC NHẬN CHẤM CÔNG NGAY
                        </button>
                    </form>
                @endif
            </div>
            <div class="card-footer text-muted small">
                Công ty: {{ Auth::user()->company->name ?? 'Chưa xác định' }}
            </div>
        </div>
    </div>
</div>
@endif

@endsection